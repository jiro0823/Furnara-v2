<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';
require_once dirname(__DIR__, 2) . '/includes/order_inventory.php';
header('Content-Type: text/plain; charset=utf-8');

function checkout_delivery_fail(string $message, int $status = 400): never
{
    http_response_code($status);
    echo $message;
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') checkout_delivery_fail('Unsupported checkout request.', 405);
if (empty($_SESSION['user_id'])) checkout_delivery_fail('Please log in before checkout.', 401);
if (empty($_SESSION['cart_csrf_token']) || !hash_equals($_SESSION['cart_csrf_token'], (string)($_POST['csrf_token'] ?? ''))) {
    checkout_delivery_fail('Your session expired. Refresh the page and try again.', 403);
}
$userId = (int)$_SESSION['user_id'];
$name = trim((string)($_POST['deliveryName'] ?? ''));
$contact = trim((string)($_POST['deliveryContact'] ?? ''));
$deliveryDate = trim((string)($_POST['deliveryDate'] ?? ''));
$deliveryTime = trim((string)($_POST['deliveryTime'] ?? ''));
$requestedSubtotal = filter_var($_POST['totalAmount'] ?? null, FILTER_VALIDATE_FLOAT);
$dateValue = DateTimeImmutable::createFromFormat('!Y-m-d', $deliveryDate);
$timeValue = DateTimeImmutable::createFromFormat('!H:i', $deliveryTime);
if ($name === '' || mb_strlen($name) > 120 || $contact === '' || mb_strlen($contact) > 40 ||
    !$dateValue || $dateValue->format('Y-m-d') !== $deliveryDate || !$timeValue || $requestedSubtotal === false || $requestedSubtotal === null) {
    checkout_delivery_fail('Enter valid delivery details and try again.');
}

try {
    $conn->begin_transaction();
    $locationQuery = $conn->prepare('SELECT delivery_address, delivery_latitude, delivery_longitude FROM users WHERE id = ? LIMIT 1');
    if (!$locationQuery) throw new RuntimeException('Your saved delivery location could not be checked.');
    $locationQuery->bind_param('i', $userId);
    $locationQuery->execute();
    $savedLocation = $locationQuery->get_result()->fetch_assoc();
    $locationQuery->close();
    if (!$savedLocation || trim((string)$savedLocation['delivery_address']) === '' || $savedLocation['delivery_latitude'] === null || $savedLocation['delivery_longitude'] === null) {
        throw new RuntimeException('Please pin and save your delivery location in your profile before checkout.');
    }
    $address = (string)$savedLocation['delivery_address'];
    $latitude = (float)$savedLocation['delivery_latitude'];
    $longitude = (float)$savedLocation['delivery_longitude'];
    $items = order_inventory_snapshot($conn, $userId);
    $subtotal = 0.0;
    $itemNames = [];
    foreach ($items as $item) {
        $subtotal += $item['unit_price'] * $item['quantity'];
        $itemNames[] = $item['product_name'] . ' x' . $item['quantity'];
    }
    $subtotal = round($subtotal, 2);
    if ($subtotal <= 0 || abs($requestedSubtotal - $subtotal) > 0.01) throw new RuntimeException('Your cart total changed. Review your cart and try again.');
    $deliveryFee = 100.00;
    $total = round($subtotal + $deliveryFee, 2);
    $furnitureName = implode(', ', $itemNames);
    $createdAt = date('Y-m-d H:i:s');
    $deliveryType = 'Delivery';
    $status = 'Pending';
    $publicOrderNumber = strtoupper(bin2hex(random_bytes(10)));

    $order = $conn->prepare('INSERT INTO orders (user_id, delivery_type, total_price, order_date, status, furniture_name, total_amount, delivery_fee, delivery_name, delivery_address, delivery_latitude, delivery_longitude, delivery_contact, public_order_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    if (!$order) throw new RuntimeException('The order could not be created. Please try again.');
    $order->bind_param('isdsssddssddss', $userId, $deliveryType, $total, $createdAt, $status, $furnitureName, $total, $deliveryFee, $name, $address, $latitude, $longitude, $contact, $publicOrderNumber);
    if (!$order->execute()) throw new RuntimeException('The order could not be created. Please try again.');
    $orderId = (int)$order->insert_id;
    $order->close();

    order_inventory_insert_items($conn, $orderId, $userId, $items);
    $payment = $conn->prepare("INSERT INTO delivery_payment (user_id, order_id, total_amount, delivery_name, furniture_name, delivery_address, delivery_contact, delivery_date, delivery_time, created_at, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
    if (!$payment) throw new RuntimeException('Delivery details could not be saved.');
    $payment->bind_param('iidsssssss', $userId, $orderId, $total, $name, $furnitureName, $address, $contact, $deliveryDate, $deliveryTime, $createdAt);
    if (!$payment->execute()) throw new RuntimeException('Delivery details could not be saved.');
    $payment->close();

    $trackingStatus = 'Preparing';
    $tracking = $conn->prepare('INSERT INTO delivery_tracking (order_id, user_id, customer_name, delivery_address, status) VALUES (?, ?, ?, ?, ?)');
    if (!$tracking) throw new RuntimeException('Delivery tracking could not be started.');
    $tracking->bind_param('iisss', $orderId, $userId, $name, $address, $trackingStatus);
    if (!$tracking->execute()) throw new RuntimeException('Delivery tracking could not be started.');
    $tracking->close();

    $delete = $conn->prepare("DELETE FROM cart_items WHERE user_id = ? AND status = 'active'");
    if (!$delete) throw new RuntimeException('The checked-out cart could not be cleared.');
    $delete->bind_param('i', $userId);
    if (!$delete->execute()) throw new RuntimeException('The checked-out cart could not be cleared.');
    $delete->close();

    $conn->commit();
    echo 'Delivery order placed successfully.';
} catch (Throwable $error) {
    $conn->rollback();
    error_log('Delivery checkout failed: ' . $error->getMessage());
    checkout_delivery_fail($error instanceof RuntimeException ? $error->getMessage() : 'Checkout failed. Please try again.', 500);
}
