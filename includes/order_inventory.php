<?php

/** Load and lock a customer's active cart. Legacy unreserved rows reserve stock here. */
function order_inventory_snapshot(mysqli $conn, int $userId): array
{
    $statement = $conn->prepare("SELECT id, furniture_id, quantity, name, price, stock_reserved FROM cart_items WHERE user_id = ? AND status = 'active' AND quantity > 0 ORDER BY id FOR UPDATE");
    if (!$statement) throw new RuntimeException('Your cart could not be loaded. Please try again.');
    $statement->bind_param('i', $userId);
    $statement->execute();
    $result = $statement->get_result();
    $cartItems = [];
    while ($row = $result->fetch_assoc()) $cartItems[] = $row;
    $statement->close();
    if (!$cartItems) throw new RuntimeException('Your cart is empty. Add an item before checkout.');

    $items = [];
    foreach ($cartItems as $row) {
        $productId = (int)$row['furniture_id'];
        $quantity = (int)$row['quantity'];
        if ($productId < 1 || $quantity < 1) throw new RuntimeException('A cart item has an invalid quantity. Remove it and try again.');

        if (empty($row['stock_reserved'])) {
            $stockStatement = $conn->prepare('SELECT stock FROM furniture WHERE furniture_id = ? FOR UPDATE');
            if (!$stockStatement) throw new RuntimeException('Product availability could not be checked.');
            $stockStatement->bind_param('i', $productId);
            $stockStatement->execute();
            $product = $stockStatement->get_result()->fetch_assoc();
            $stockStatement->close();
            $available = (int)($product['stock'] ?? 0);
            if (!$product || $available < $quantity) {
                throw new RuntimeException($available > 0
                    ? "Only {$available} unit(s) remain available. Update your cart before checkout."
                    : 'A product in your cart is out of stock. Update your cart before checkout.');
            }
            $reserve = $conn->prepare('UPDATE furniture SET stock = stock - ? WHERE furniture_id = ? AND stock >= ?');
            if (!$reserve) throw new RuntimeException('Product availability could not be reserved.');
            $reserve->bind_param('iii', $quantity, $productId, $quantity);
            $didReserve = $reserve->execute() && $reserve->affected_rows === 1;
            $reserve->close();
            if (!$didReserve) throw new RuntimeException('Stock changed during checkout. Please refresh your cart.');

            $mark = $conn->prepare('UPDATE cart_items SET stock_reserved = 1 WHERE id = ? AND user_id = ?');
            if (!$mark) throw new RuntimeException('Your cart reservation could not be saved.');
            $cartId = (int)$row['id'];
            $mark->bind_param('ii', $cartId, $userId);
            if (!$mark->execute() || $mark->affected_rows !== 1) {
                $mark->close();
                throw new RuntimeException('Your cart reservation could not be saved.');
            }
            $mark->close();
        }

        $items[] = [
            'cart_id' => (int)$row['id'],
            'furniture_id' => $productId,
            'product_name' => (string)$row['name'],
            'quantity' => $quantity,
            'unit_price' => (float)$row['price'],
        ];
    }
    return $items;
}

function order_inventory_insert_items(mysqli $conn, int $orderId, int $userId, array $items): void
{
    $statement = $conn->prepare('INSERT INTO order_items (order_id, user_id, furniture_id, product_name, quantity, unit_price) VALUES (?, ?, ?, ?, ?, ?)');
    if (!$statement) throw new RuntimeException('Order item details could not be saved.');
    foreach ($items as $item) {
        $productId = (int)$item['furniture_id'];
        $productName = (string)$item['product_name'];
        $quantity = (int)$item['quantity'];
        $unitPrice = (float)$item['unit_price'];
        $statement->bind_param('iiisid', $orderId, $userId, $productId, $productName, $quantity, $unitPrice);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Order item details could not be saved.');
        }
    }
    $statement->close();
}

/** Return stock once for each order line; caller must hold a transaction and lock the order. */
function order_inventory_restore_cancelled(mysqli $conn, int $orderId, int $userId): void
{
    $statement = $conn->prepare('SELECT order_item_id, furniture_id, quantity FROM order_items WHERE order_id = ? AND user_id = ? AND stock_restored = 0 FOR UPDATE');
    if (!$statement) throw new RuntimeException('Order inventory could not be loaded.');
    $statement->bind_param('ii', $orderId, $userId);
    $statement->execute();
    $result = $statement->get_result();
    $items = [];
    while ($row = $result->fetch_assoc()) $items[] = $row;
    $statement->close();

    foreach ($items as $item) {
        $restore = $conn->prepare('UPDATE furniture SET stock = stock + ? WHERE furniture_id = ?');
        if (!$restore) throw new RuntimeException('Cancelled order stock could not be restored.');
        $quantity = (int)$item['quantity'];
        $productId = (int)$item['furniture_id'];
        $restore->bind_param('ii', $quantity, $productId);
        if (!$restore->execute() || $restore->affected_rows !== 1) {
            $restore->close();
            throw new RuntimeException('Cancelled order stock could not be restored.');
        }
        $restore->close();

        $mark = $conn->prepare('UPDATE order_items SET stock_restored = 1 WHERE order_item_id = ? AND order_id = ? AND user_id = ? AND stock_restored = 0');
        if (!$mark) throw new RuntimeException('Stock restoration could not be recorded.');
        $itemId = (int)$item['order_item_id'];
        $mark->bind_param('iii', $itemId, $orderId, $userId);
        if (!$mark->execute() || $mark->affected_rows !== 1) {
            $mark->close();
            throw new RuntimeException('Stock restoration could not be recorded.');
        }
        $mark->close();
    }
}
