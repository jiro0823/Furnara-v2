<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';
header('Content-Type: application/json; charset=utf-8');

function prepare_checkout_reply(array $body, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($body);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') prepare_checkout_reply(['success' => false, 'message' => 'Unsupported request.'], 405);
if (empty($_SESSION['user_id'])) prepare_checkout_reply(['success' => false, 'message' => 'Please log in before checkout.'], 401);
if (empty($_SESSION['cart_csrf_token']) || !hash_equals($_SESSION['cart_csrf_token'], (string)($_POST['csrf_token'] ?? ''))) {
    prepare_checkout_reply(['success' => false, 'message' => 'Your session expired. Refresh the page and try again.'], 403);
}

$userId = (int)$_SESSION['user_id'];
$type = (string)($_POST['order_type'] ?? '');
$draft = ['order_type' => $type, 'token' => bin2hex(random_bytes(32)), 'created_at' => time()];
if ($type === 'instore') {
    $date = trim((string)($_POST['pickupDate'] ?? ''));
    $time = trim((string)($_POST['pickupTime'] ?? ''));
    $dateValue = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    $timeValue = DateTimeImmutable::createFromFormat('!H:i', $time);
    $scheduledAt = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $date . ' ' . $time);
    if (!$dateValue || $dateValue->format('Y-m-d') !== $date || !$timeValue || !$scheduledAt
        || $scheduledAt->format('Y-m-d H:i') !== $date . ' ' . $time || $scheduledAt <= new DateTimeImmutable()) {
        prepare_checkout_reply(['success' => false, 'message' => 'Choose a valid pickup date and time.']);
    }
    $draft += ['pickup_date' => $date, 'pickup_time' => $time];
} elseif ($type === 'delivery') {
    $name = trim((string)($_POST['deliveryName'] ?? ''));
    $contact = trim((string)($_POST['deliveryContact'] ?? ''));
    $date = trim((string)($_POST['deliveryDate'] ?? ''));
    $time = trim((string)($_POST['deliveryTime'] ?? ''));
    $dateValue = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    $timeValue = DateTimeImmutable::createFromFormat('!H:i', $time);
    $scheduledAt = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $date . ' ' . $time);
    if ($name === '' || mb_strlen($name) > 120 || $contact === '' || mb_strlen($contact) > 40
        || !$dateValue || $dateValue->format('Y-m-d') !== $date || !$timeValue || !$scheduledAt
        || $scheduledAt->format('Y-m-d H:i') !== $date . ' ' . $time || $scheduledAt <= new DateTimeImmutable()) {
        prepare_checkout_reply(['success' => false, 'message' => 'Enter valid delivery details and try again.']);
    }
    $location = $conn->prepare('SELECT delivery_address, delivery_latitude, delivery_longitude FROM users WHERE id = ? LIMIT 1');
    if (!$location) prepare_checkout_reply(['success' => false, 'message' => 'Delivery location could not be checked.'], 500);
    $location->bind_param('i', $userId);
    $location->execute();
    $saved = $location->get_result()->fetch_assoc();
    $location->close();
    if (!$saved || trim((string)$saved['delivery_address']) === '' || $saved['delivery_latitude'] === null || $saved['delivery_longitude'] === null) {
        prepare_checkout_reply(['success' => false, 'message' => 'Please pin and save your delivery location in your profile before checkout.']);
    }
    $draft += ['delivery_name' => $name, 'delivery_contact' => $contact, 'delivery_date' => $date, 'delivery_time' => $time];
} else {
    prepare_checkout_reply(['success' => false, 'message' => 'Choose a valid order type.']);
}

$stmt = $conn->prepare("SELECT COUNT(*) AS item_count, COALESCE(SUM(quantity * price), 0) AS subtotal FROM cart_items WHERE user_id = ? AND status = 'active' AND quantity > 0");
if (!$stmt) prepare_checkout_reply(['success' => false, 'message' => 'Your cart could not be checked.'], 500);
$stmt->bind_param('i', $userId);
$stmt->execute();
$cart = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$cart || (int)$cart['item_count'] === 0 || (float)$cart['subtotal'] <= 0) prepare_checkout_reply(['success' => false, 'message' => 'Your cart is empty. Add a product before checkout.']);

$_SESSION['checkout_draft'] = $draft;
prepare_checkout_reply(['success' => true, 'redirect' => app_url('pages/orders/payment_checkout.php'), 'subtotal' => (float)$cart['subtotal']]);
