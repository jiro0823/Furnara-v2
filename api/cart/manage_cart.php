<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';
header('Content-Type: application/json; charset=utf-8');

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

function cart_json(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function cart_recommendations(mysqli $connection, string $type, int $excludeId): array
{
    $statement = mysqli_prepare($connection, 'SELECT furniture_id, name, image, price FROM furniture WHERE LOWER(furniture_type) = LOWER(?) AND stock > 0 AND furniture_id <> ? ORDER BY furniture_id DESC LIMIT 3');
    if (!$statement) return [];
    mysqli_stmt_bind_param($statement, 'si', $type, $excludeId);
    mysqli_stmt_execute($statement);
    $result = mysqli_stmt_get_result($statement);
    $suggestions = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $suggestions[] = [
            'id' => (int)$row['furniture_id'],
            'name' => (string)$row['name'],
            'url' => app_url('pages/public/product.php?id=' . (int)$row['furniture_id']),
        ];
    }
    mysqli_stmt_close($statement);
    return $suggestions;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!$userId) cart_json(['success' => true, 'items' => [], 'total' => 0, 'count' => 0]);
    $sql = "SELECT c.furniture_id, c.quantity, f.name,
                   COALESCE(CASE WHEN f.discount_percentage > 0 AND f.discount_start <= CURDATE() AND f.discount_end >= CURDATE() THEN f.discounted_price ELSE f.price END, f.price) AS price,
                   f.image, f.stock
            FROM cart_items c JOIN furniture f ON f.furniture_id = c.furniture_id
            WHERE c.status = 'active' AND c.quantity > 0 AND c.user_id = ?
            ORDER BY c.id DESC";
    $statement = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($statement, 'i', $userId);
    mysqli_stmt_execute($statement);
    $result = mysqli_stmt_get_result($statement);
    $items = [];
    $total = 0.0;
    $count = 0;
    while ($item = mysqli_fetch_assoc($result)) {
        $item['furniture_id'] = (int)$item['furniture_id'];
        $item['quantity'] = (int)$item['quantity'];
        $item['price'] = (float)$item['price'];
        $item['stock'] = (int)$item['stock'];
        $item['image_url'] = $item['image'] !== '' ? app_url('uploads/' . rawurlencode((string)$item['image'])) : app_url('pictures/bg-chair.png');
        $total += $item['price'] * $item['quantity'];
        $count += $item['quantity'];
        $items[] = $item;
    }
    mysqli_stmt_close($statement);
    cart_json(['success' => true, 'items' => $items, 'total' => $total, 'count' => $count]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') cart_json(['success' => false, 'message' => 'Unsupported request.'], 405);
if (!$userId) cart_json(['success' => false, 'message' => 'Please log in before using your cart.'], 401);
if (empty($_SESSION['cart_csrf_token']) || !hash_equals($_SESSION['cart_csrf_token'], (string)($_POST['csrf_token'] ?? ''))) {
    cart_json(['success' => false, 'message' => 'Your session expired. Refresh the page and try again.'], 403);
}

$action = (string)($_POST['action'] ?? '');
$furnitureId = filter_var($_POST['furniture_id'] ?? null, FILTER_VALIDATE_INT);
if (!$furnitureId || $furnitureId < 1) cart_json(['success' => false, 'message' => 'Choose a valid product.'], 400);
if (!in_array($action, ['add', 'set', 'remove'], true)) cart_json(['success' => false, 'message' => 'Unsupported cart action.'], 400);

$requestedQuantity = null;
if ($action !== 'remove') {
    $requestedQuantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT);
    if ($requestedQuantity === false || $requestedQuantity === null || $requestedQuantity < 0 || ($action === 'add' && $requestedQuantity < 1)) {
        cart_json(['success' => false, 'message' => 'Enter a valid quantity.'], 400);
    }
}

try {
    $conn->begin_transaction();

    // Lock inventory first so concurrent customers cannot reserve the same units.
    $productStatement = $conn->prepare('SELECT name, furniture_type, COALESCE(CASE WHEN discount_percentage > 0 AND discount_start <= CURDATE() AND discount_end >= CURDATE() THEN discounted_price ELSE price END, price) AS price, image, stock FROM furniture WHERE furniture_id = ? FOR UPDATE');
    if (!$productStatement) throw new RuntimeException('Inventory could not be checked. Try again.');
    $productStatement->bind_param('i', $furnitureId);
    if (!$productStatement->execute()) throw new RuntimeException('Inventory could not be checked. Try again.');
    $product = $productStatement->get_result()->fetch_assoc();
    $productStatement->close();
    if (!$product) {
        $conn->rollback();
        cart_json(['success' => false, 'message' => 'Product not found.'], 404);
    }

    $find = $conn->prepare("SELECT id, quantity, stock_reserved FROM cart_items WHERE furniture_id = ? AND status = 'active' AND user_id = ? ORDER BY id DESC LIMIT 1 FOR UPDATE");
    if (!$find) throw new RuntimeException('Your cart could not be checked. Try again.');
    $find->bind_param('ii', $furnitureId, $userId);
    if (!$find->execute()) throw new RuntimeException('Your cart could not be checked. Try again.');
    $existing = $find->get_result()->fetch_assoc();
    $find->close();

    $oldQuantity = (int)($existing['quantity'] ?? 0);
    $oldReservedQuantity = !empty($existing['stock_reserved']) ? $oldQuantity : 0;
    $targetQuantity = $action === 'remove' ? 0 : ($action === 'add' ? $oldQuantity + $requestedQuantity : $requestedQuantity);

    if ($targetQuantity === 0) {
        if ($existing) {
            if ($oldReservedQuantity > 0) {
                $restore = $conn->prepare('UPDATE furniture SET stock = stock + ? WHERE furniture_id = ?');
                if (!$restore) throw new RuntimeException('Inventory could not be released. Try again.');
                $restore->bind_param('ii', $oldReservedQuantity, $furnitureId);
                if (!$restore->execute() || $restore->affected_rows !== 1) {
                    $restore->close();
                    throw new RuntimeException('Inventory could not be released. Try again.');
                }
                $restore->close();
            }
            $delete = $conn->prepare('DELETE FROM cart_items WHERE id = ? AND user_id = ?');
            if (!$delete) throw new RuntimeException('The cart item could not be removed.');
            $itemId = (int)$existing['id'];
            $delete->bind_param('ii', $itemId, $userId);
            if (!$delete->execute() || $delete->affected_rows !== 1) {
                $delete->close();
                throw new RuntimeException('The cart item could not be removed.');
            }
            $delete->close();
        }
        $conn->commit();
        cart_json(['success' => true, 'message' => 'Product removed from cart.']);
    }

    $stock = (int)$product['stock'];
    $reservationDelta = $targetQuantity - $oldReservedQuantity;
    if ($reservationDelta > $stock) {
        $conn->rollback();
        cart_json([
            'success' => false,
            'message' => $stock > 0 ? "Only {$stock} more unit(s) are currently available." : 'This product is out of stock.',
            'recommendations' => cart_recommendations($conn, (string)$product['furniture_type'], $furnitureId),
        ], 409);
    }

    if ($reservationDelta > 0) {
        $reserve = $conn->prepare('UPDATE furniture SET stock = stock - ? WHERE furniture_id = ? AND stock >= ?');
        if (!$reserve) throw new RuntimeException('Inventory could not be reserved. Try again.');
        $reserve->bind_param('iii', $reservationDelta, $furnitureId, $reservationDelta);
        $reserved = $reserve->execute() && $reserve->affected_rows === 1;
        $reserve->close();
        if (!$reserved) {
            $conn->rollback();
            cart_json(['success' => false, 'message' => 'Stock changed while you were updating the cart. Refresh and try again.', 'recommendations' => cart_recommendations($conn, (string)$product['furniture_type'], $furnitureId)], 409);
        }
    } elseif ($reservationDelta < 0) {
        $releaseQuantity = abs($reservationDelta);
        $release = $conn->prepare('UPDATE furniture SET stock = stock + ? WHERE furniture_id = ?');
        if (!$release) throw new RuntimeException('Inventory could not be released. Try again.');
        $release->bind_param('ii', $releaseQuantity, $furnitureId);
        if (!$release->execute() || $release->affected_rows !== 1) {
            $release->close();
            throw new RuntimeException('Inventory could not be released. Try again.');
        }
        $release->close();
    }

    $price = (float)$product['price'];
    if ($existing) {
        $itemId = (int)$existing['id'];
        $save = $conn->prepare('UPDATE cart_items SET quantity = ?, name = ?, price = ?, image = ?, stock_reserved = 1 WHERE id = ? AND user_id = ?');
        if (!$save) throw new RuntimeException('Your cart could not be saved. Try again.');
        $save->bind_param('isdsii', $targetQuantity, $product['name'], $price, $product['image'], $itemId, $userId);
    } else {
        $save = $conn->prepare("INSERT INTO cart_items (user_id, furniture_id, quantity, name, price, image, status, stock_reserved) VALUES (?, ?, ?, ?, ?, ?, 'active', 1)");
        if (!$save) throw new RuntimeException('Your cart could not be saved. Try again.');
        $save->bind_param('iiisds', $userId, $furnitureId, $targetQuantity, $product['name'], $price, $product['image']);
    }
    if (!$save->execute()) throw new RuntimeException('Your cart could not be saved. Try again.');
    $save->close();
    $conn->commit();
    cart_json(['success' => true, 'message' => 'Cart updated.', 'available_stock' => max(0, $stock - $reservationDelta)]);
} catch (Throwable $error) {
    $conn->rollback();
    error_log('Cart inventory update failed: ' . $error->getMessage());
    cart_json(['success' => false, 'message' => $error instanceof RuntimeException ? $error->getMessage() : 'Could not update your cart. Please try again.'], 500);
}
