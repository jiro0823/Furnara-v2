<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';
require_once dirname(__DIR__, 2) . '/includes/order_inventory.php';
header('Content-Type: application/json; charset=utf-8');

function finalize_checkout_reply(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') finalize_checkout_reply(['success' => false, 'message' => 'Unsupported request.'], 405);
if (empty($_SESSION['user_id'])) finalize_checkout_reply(['success' => false, 'message' => 'Please log in before checkout.'], 401);
if (empty($_SESSION['cart_csrf_token']) || !hash_equals($_SESSION['cart_csrf_token'], (string)($_POST['csrf_token'] ?? ''))) {
    finalize_checkout_reply(['success' => false, 'message' => 'Your session expired. Refresh and try again.'], 403);
}
$userId = (int)$_SESSION['user_id'];
$draft = $_SESSION['checkout_draft'] ?? null;
if (!is_array($draft) || empty($draft['token']) || time() - (int)($draft['created_at'] ?? 0) > 1800) {
    unset($_SESSION['checkout_draft']);
    finalize_checkout_reply(['success' => false, 'message' => 'Your checkout session expired. Return to your cart and try again.'], 409);
}
$orderType = (string)$draft['order_type'];
$method = (string)($_POST['payment_method'] ?? '');
$allowedMethod = $orderType === 'instore' ? ['pay_at_store'] : ['cash_on_delivery'];
if (!in_array($method, $allowedMethod, true)) finalize_checkout_reply(['success' => false, 'message' => 'Choose a valid payment method.']);
$paymentStatus = 'Pending';

try {
    $conn->begin_transaction();
    $items = order_inventory_snapshot($conn, $userId);
    $subtotal = 0.0;
    $itemNames = [];
    foreach ($items as $item) {
        $subtotal += $item['unit_price'] * $item['quantity'];
        $itemNames[] = $item['product_name'] . ' x' . $item['quantity'];
    }
    $subtotal = round($subtotal, 2);
    $deliveryFee = $orderType === 'delivery' ? 100.00 : 0.00;
    $total = round($subtotal + $deliveryFee, 2);
    if ($subtotal <= 0) throw new RuntimeException('Your cart is empty. Add a product before checkout.');
    $furnitureName = implode(', ', $itemNames);
    $createdAt = date('Y-m-d H:i:s');
    $deliveryType = $orderType === 'delivery' ? 'Delivery' : 'In-Store';
    $status = 'Pending';
    $publicOrderNumber = strtoupper(bin2hex(random_bytes(10)));
    if ($orderType === 'instore') {
        $pickupDate = (string)$draft['pickup_date'];
        $pickupTime = (string)$draft['pickup_time'];
        $order = $conn->prepare('INSERT INTO orders (user_id, delivery_type, total_price, order_date, status, furniture_name, total_amount, pickup_date, pickup_time, public_order_number, payment_method, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        if (!$order) throw new RuntimeException('The order could not be created. Run the latest order payment migration.');
        $order->bind_param('isdsssdsssss', $userId, $deliveryType, $total, $createdAt, $status, $furnitureName, $total, $pickupDate, $pickupTime, $publicOrderNumber, $method, $paymentStatus);
        if (!$order->execute()) throw new RuntimeException('The order could not be created. Please try again.');
        $orderId = (int)$order->insert_id;
        $order->close();
        order_inventory_insert_items($conn, $orderId, $userId, $items);
        $payment = $conn->prepare('INSERT INTO instore_payment (user_id, order_id, total_amount, furniture_name, pickup_date, pickup_time, created_at, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        if (!$payment) throw new RuntimeException('Pickup payment details could not be saved.');
        $payment->bind_param('iidsssss', $userId, $orderId, $total, $furnitureName, $pickupDate, $pickupTime, $createdAt, $paymentStatus);
        if (!$payment->execute()) throw new RuntimeException('Pickup payment details could not be saved.');
        $payment->close();
    } elseif ($orderType === 'delivery') {
        $locationQuery = $conn->prepare('SELECT delivery_address, delivery_latitude, delivery_longitude FROM users WHERE id = ? LIMIT 1 FOR UPDATE');
        if (!$locationQuery) throw new RuntimeException('Your saved delivery location could not be checked.');
        $locationQuery->bind_param('i', $userId);
        $locationQuery->execute();
        $location = $locationQuery->get_result()->fetch_assoc();
        $locationQuery->close();
        if (!$location || trim((string)$location['delivery_address']) === '' || $location['delivery_latitude'] === null || $location['delivery_longitude'] === null) {
            throw new RuntimeException('Please pin and save your delivery location in your profile before checkout.');
        }
        $name = (string)$draft['delivery_name'];
        $contact = (string)$draft['delivery_contact'];
        $address = (string)$location['delivery_address'];
        $latitude = (float)$location['delivery_latitude'];
        $longitude = (float)$location['delivery_longitude'];
        $deliveryDate = (string)$draft['delivery_date'];
        $deliveryTime = (string)$draft['delivery_time'];
        $order = $conn->prepare('INSERT INTO orders (user_id, delivery_type, total_price, order_date, status, furniture_name, total_amount, delivery_fee, delivery_name, delivery_address, delivery_latitude, delivery_longitude, delivery_contact, public_order_number, payment_method, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        if (!$order) throw new RuntimeException('The order could not be created. Run the latest order payment migration.');
        $order->bind_param('isdsssddssddssss', $userId, $deliveryType, $total, $createdAt, $status, $furnitureName, $total, $deliveryFee, $name, $address, $latitude, $longitude, $contact, $publicOrderNumber, $method, $paymentStatus);
        if (!$order->execute()) throw new RuntimeException('The order could not be created. Please try again.');
        $orderId = (int)$order->insert_id;
        $order->close();
        order_inventory_insert_items($conn, $orderId, $userId, $items);
        $payment = $conn->prepare('INSERT INTO delivery_payment (user_id, order_id, total_amount, delivery_name, furniture_name, delivery_address, delivery_contact, delivery_date, delivery_time, created_at, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        if (!$payment) throw new RuntimeException('Delivery payment details could not be saved.');
        $payment->bind_param('iidssssssss', $userId, $orderId, $total, $name, $furnitureName, $address, $contact, $deliveryDate, $deliveryTime, $createdAt, $paymentStatus);
        if (!$payment->execute()) throw new RuntimeException('Delivery payment details could not be saved.');
        $payment->close();
        $trackingStatus = 'Preparing';
        $tracking = $conn->prepare('INSERT INTO delivery_tracking (order_id, user_id, customer_name, delivery_address, status) VALUES (?, ?, ?, ?, ?)');
        if (!$tracking) throw new RuntimeException('Delivery tracking could not be started.');
        $tracking->bind_param('iisss', $orderId, $userId, $name, $address, $trackingStatus);
        if (!$tracking->execute()) throw new RuntimeException('Delivery tracking could not be started.');
        $tracking->close();
    } else {
        throw new RuntimeException('Invalid order type.');
    }

    $clear = $conn->prepare("DELETE FROM cart_items WHERE user_id = ? AND status = 'active'");
    if (!$clear) throw new RuntimeException('The checked-out cart could not be cleared.');
    $clear->bind_param('i', $userId);
    if (!$clear->execute()) throw new RuntimeException('The checked-out cart could not be cleared.');
    $clear->close();
    $conn->commit();
    unset($_SESSION['checkout_draft']);
    $_SESSION['checkout_success'] = 'Your order #' . $publicOrderNumber . ' has been checked out successfully.';
    finalize_checkout_reply(['success' => true, 'message' => 'Order placed successfully.', 'redirect' => app_url('pages/public/user.php')]);
} catch (Throwable $error) {
    $conn->rollback();
    error_log('Checkout finalization failed: ' . $error->getMessage());
    finalize_checkout_reply(['success' => false, 'message' => $error instanceof RuntimeException ? $error->getMessage() : 'Checkout failed. Please try again.'], 500);
}
