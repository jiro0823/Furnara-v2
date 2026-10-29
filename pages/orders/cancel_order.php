<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';
require_once dirname(__DIR__, 2) . '/includes/order_inventory.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ' . app_url('pages/auth/login.php'));
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['account_csrf_token']) || !hash_equals($_SESSION['account_csrf_token'], (string)($_POST['csrf_token'] ?? ''))) {
    http_response_code(403);
    exit('Invalid request. Refresh your orders page and try again.');
}

$userId = (int)$_SESSION['user_id'];
$returnToDrawer = ($_POST['drawer'] ?? '') === '1';
$ordersUrl = app_url('pages/account/Orders.php' . ($returnToDrawer ? '?drawer=1' : ''));
$orderNumber = strtoupper(trim((string)($_POST['order_number'] ?? '')));
if (!preg_match('/^[A-F0-9]{20}$/', $orderNumber)) {
    header('Location: ' . $ordersUrl);
    exit;
}

$conn->begin_transaction();
try {
    $lookup = $conn->prepare('SELECT order_id, status FROM orders WHERE public_order_number = ? AND user_id = ? LIMIT 1 FOR UPDATE');
    if (!$lookup) throw new RuntimeException('The order could not be loaded.');
    $lookup->bind_param('si', $orderNumber, $userId);
    $lookup->execute();
    $order = $lookup->get_result()->fetch_assoc();
    $lookup->close();
    if (!$order) {
        $conn->rollback();
        header('Location: ' . $ordersUrl);
        exit;
    }

    $orderId = (int)$order['order_id'];
    $statusLookup = $conn->prepare("SELECT COALESCE((SELECT NULLIF(dt.status, '') FROM delivery_tracking dt WHERE dt.order_id = ? AND dt.user_id = ? ORDER BY dt.tracking_id DESC LIMIT 1), (SELECT NULLIF(dp.status, '') FROM delivery_payment dp WHERE dp.order_id = ? AND dp.user_id = ? ORDER BY dp.id DESC LIMIT 1), (SELECT NULLIF(ip.status, '') FROM instore_payment ip WHERE ip.order_id = ? AND ip.user_id = ? ORDER BY ip.id DESC LIMIT 1), ?) AS current_status");
    if (!$statusLookup) throw new RuntimeException('The order status could not be checked.');
    $statusLookup->bind_param('iiiiiis', $orderId, $userId, $orderId, $userId, $orderId, $userId, $order['status']);
    $statusLookup->execute();
    $currentStatus = (string)($statusLookup->get_result()->fetch_assoc()['current_status'] ?? $order['status']);
    $statusLookup->close();
    if (in_array(strtolower($currentStatus), ['delivered', 'received', 'completed', 'cancelled', 'canceled'], true)) {
        $conn->rollback();
        header('Location: ' . $ordersUrl . (str_contains($ordersUrl, '?') ? '&' : '?') . 'status=all&message=not-cancellable');
        exit;
    }

    order_inventory_restore_cancelled($conn, $orderId, $userId);
    foreach ([
        "UPDATE orders SET status = 'Cancelled' WHERE order_id = ? AND user_id = ?",
        "UPDATE delivery_payment SET status = 'Cancelled' WHERE order_id = ? AND user_id = ?",
        "UPDATE instore_payment SET status = 'Cancelled' WHERE order_id = ? AND user_id = ?",
        "UPDATE delivery_tracking SET status = 'Cancelled' WHERE order_id = ? AND user_id = ?",
    ] as $sql) {
        $statement = $conn->prepare($sql);
        if (!$statement) throw new RuntimeException('The order status could not be updated.');
        $statement->bind_param('ii', $orderId, $userId);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('The order status could not be updated.');
        }
        $statement->close();
    }

    $conn->commit();
    header('Location: ' . $ordersUrl . (str_contains($ordersUrl, '?') ? '&' : '?') . 'status=cancelled');
    exit;
} catch (Throwable $exception) {
    $conn->rollback();
    error_log('Order cancellation failed: ' . $exception->getMessage());
    http_response_code(500);
    exit('The order could not be cancelled. Please contact support.');
}
