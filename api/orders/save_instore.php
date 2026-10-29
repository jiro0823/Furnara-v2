<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';
require_once dirname(__DIR__, 2) . '/includes/order_inventory.php';
header('Content-Type: text/plain; charset=utf-8');

function checkout_pickup_fail(string $message, int $status = 400): never
{
    http_response_code($status);
    echo $message;
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') checkout_pickup_fail('Unsupported checkout request.', 405);
if (empty($_SESSION['user_id'])) checkout_pickup_fail('Please log in before checkout.', 401);
if (empty($_SESSION['cart_csrf_token']) || !hash_equals($_SESSION['cart_csrf_token'], (string)($_POST['csrf_token'] ?? ''))) {
    checkout_pickup_fail('Your session expired. Refresh the page and try again.', 403);
}

$userId = (int)$_SESSION['user_id'];
$pickupDate = trim((string)($_POST['pickupDate'] ?? ''));
$pickupTime = trim((string)($_POST['pickupTime'] ?? ''));
$requestedSubtotal = filter_var($_POST['totalAmount'] ?? null, FILTER_VALIDATE_FLOAT);
$dateValue = DateTimeImmutable::createFromFormat('!Y-m-d', $pickupDate);
$timeValue = DateTimeImmutable::createFromFormat('!H:i', $pickupTime);
if (!$dateValue || $dateValue->format('Y-m-d') !== $pickupDate || !$timeValue || $requestedSubtotal === false || $requestedSubtotal === null || $requestedSubtotal < 0) {
    checkout_pickup_fail('Choose a valid pickup date and time.');
}

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
    if ($subtotal <= 0 || abs($requestedSubtotal - $subtotal) > 0.01) {
        throw new RuntimeException('Your cart total changed. Review your cart and try again.');
    }

    $furnitureName = implode(', ', $itemNames);
    $createdAt = date('Y-m-d H:i:s');
    $deliveryType = 'In-Store';
    $status = 'Pending';
    $publicOrderNumber = strtoupper(bin2hex(random_bytes(10)));
    $order = $conn->prepare('INSERT INTO orders (user_id, delivery_type, total_price, order_date, status, furniture_name, total_amount, pickup_date, pickup_time, public_order_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    if (!$order) throw new RuntimeException('The order could not be created. Please try again.');
    $order->bind_param('isdsssdsss', $userId, $deliveryType, $subtotal, $createdAt, $status, $furnitureName, $subtotal, $pickupDate, $pickupTime, $publicOrderNumber);
    if (!$order->execute()) throw new RuntimeException('The order could not be created. Please try again.');
    $orderId = (int)$order->insert_id;
    $order->close();

    order_inventory_insert_items($conn, $orderId, $userId, $items);
    $payment = $conn->prepare("INSERT INTO instore_payment (user_id, order_id, total_amount, furniture_name, pickup_date, pickup_time, created_at, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')");
    if (!$payment) throw new RuntimeException('Pickup details could not be saved.');
    $payment->bind_param('iidssss', $userId, $orderId, $subtotal, $furnitureName, $pickupDate, $pickupTime, $createdAt);
    if (!$payment->execute()) throw new RuntimeException('Pickup details could not be saved.');
    $payment->close();

    $delete = $conn->prepare("DELETE FROM cart_items WHERE user_id = ? AND status = 'active'");
    if (!$delete) throw new RuntimeException('The checked-out cart could not be cleared.');
    $delete->bind_param('i', $userId);
    if (!$delete->execute()) throw new RuntimeException('The checked-out cart could not be cleared.');
    $delete->close();

    $conn->commit();
    echo 'In-store pickup order placed successfully.';
} catch (Throwable $error) {
    $conn->rollback();
    error_log('In-store checkout failed: ' . $error->getMessage());
    checkout_pickup_fail($error instanceof RuntimeException ? $error->getMessage() : 'Checkout failed. Please try again.', 500);
}