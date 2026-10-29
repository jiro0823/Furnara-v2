<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';

if (empty($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    header('Location: ' . app_url('pages/auth/login.php'));
    exit;
}

if (empty($_SESSION['admin_order_csrf'])) {
    $_SESSION['admin_order_csrf'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['admin_order_csrf'];
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_action'])) {
    $postedToken = (string)($_POST['csrf_token'] ?? '');
    $type = (string)($_POST['payment_type'] ?? '');
    $paymentId = filter_var($_POST['payment_id'] ?? null, FILTER_VALIDATE_INT);
    $action = (string)$_POST['order_action'];

    if (!hash_equals($csrfToken, $postedToken)) {
        $notice = 'Your session expired. Refresh the page and try again.';
    } elseif (!$paymentId || !in_array($type, ['instore', 'delivery'], true) || !in_array($action, ['accept', 'deny'], true)) {
        $notice = 'That order action is invalid.';
    } else {
        $table = $type === 'instore' ? 'instore_payment' : 'delivery_payment';
        $key = $type === 'instore' ? 'order_id' : 'id';
        $status = $action === 'accept' ? 'Accepted' : 'Denied';
        $stmt = $conn->prepare("UPDATE {$table} SET status = ? WHERE {$key} = ? AND LOWER(status) = 'pending'");
        if ($stmt) {
            $stmt->bind_param('si', $status, $paymentId);
            $stmt->execute();
            $notice = $stmt->affected_rows > 0 ? 'Order ' . strtolower($status) . ' successfully.' : 'This order was already updated or could not be found.';
            $stmt->close();
        } else {
            error_log('Admin order update prepare failed: ' . $conn->error);
            $notice = 'The order could not be updated. Please try again.';
        }
    }
}

$ordersByType = ['instore' => [], 'delivery' => []];
foreach (['instore' => 'instore_payment', 'delivery' => 'delivery_payment'] as $type => $table) {
    $stmt = $conn->prepare("SELECT * FROM {$table} ORDER BY created_at DESC");
    if ($stmt && $stmt->execute()) {
        $result = $stmt->get_result();
        while ($order = $result->fetch_assoc()) {
            $order['_type'] = $type;
            $order['_payment_id'] = (int)($type === 'instore' ? ($order['order_id'] ?? 0) : ($order['id'] ?? 0));
            $ordersByType[$type][] = $order;
        }
        $stmt->close();
    } else {
        error_log("Admin {$type} orders query failed: " . $conn->error);
    }
}

$allOrders = array_merge($ordersByType['instore'], $ordersByType['delivery']);
$totalOrders = count($allOrders);
$counts = ['Pending' => 0, 'Accepted' => 0, 'Denied' => 0];
foreach ($allOrders as $order) {
    $normalized = ucfirst(strtolower((string)($order['status'] ?? 'Pending')));
    if (isset($counts[$normalized])) $counts[$normalized]++;
}

$productLookup = $conn->prepare('SELECT name, description, image, price FROM furniture WHERE LOWER(name) = LOWER(?) LIMIT 1');
$userLookup = $conn->prepare('SELECT name, email FROM users WHERE id = ? LIMIT 1');
$orderInfoLookup = $conn->prepare('SELECT delivery_name, delivery_address, public_order_number FROM orders WHERE order_id = ? AND user_id = ? LIMIT 1');

function admin_order_escape($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function admin_order_money($value): string { return '&#8369;' . number_format((float)$value, 2); }
function admin_order_products(mysqli_stmt $lookup, string $raw): array {
    $items = [];
    foreach (preg_split('/\s*,\s*/', trim($raw), -1, PREG_SPLIT_NO_EMPTY) as $part) {
        $name = trim($part);
        $quantity = 1;
        if (preg_match('/^(.*?)\s+x(\d+)$/i', $part, $matches)) {
            $name = trim($matches[1]);
            $quantity = max(1, (int)$matches[2]);
        }
        $lookup->bind_param('s', $name);
        $lookup->execute();
        $product = $lookup->get_result()->fetch_assoc() ?: [];
        $items[] = [
            'name' => $product['name'] ?? $name,
            'description' => $product['description'] ?? '',
            'image' => $product['image'] ?? '',
            'price' => (float)($product['price'] ?? 0),
            'quantity' => $quantity,
        ];
    }
    return $items;
}

$sections = [
    'pending-instore' => ['title' => 'Pending in-store orders', 'icon' => 'bi-shop-window', 'type' => 'instore', 'status' => 'Pending'],
    'pending-delivery' => ['title' => 'Pending delivery orders', 'icon' => 'bi-truck', 'type' => 'delivery', 'status' => 'Pending'],
    'accepted' => ['title' => 'Accepted orders', 'icon' => 'bi-patch-check-fill', 'type' => 'all', 'status' => 'Accepted'],
    'denied' => ['title' => 'Declined orders', 'icon' => 'bi-x-octagon-fill', 'type' => 'all', 'status' => 'Denied'],
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Orders | Furnara Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= app_url('assets/css/admin_style.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/admin_sidebar.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/admin_orders.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/support_chat_widget.css') ?>">
</head>
<body class="admin-orders-page">
<?php require dirname(__DIR__, 2) . '/components/navigation/admin_sidebar.php'; ?>
<main class="main-content admin-orders-main">
    <header class="orders-heading"><div><p class="orders-eyebrow">ORDER MANAGEMENT</p><h1><i class="bi bi-receipt"></i> Orders</h1><p class="orders-subtitle">Review customer requests and keep each order moving.</p></div></header>
    <?php if ($notice !== ''): ?><div class="alert alert-info py-2" role="status"><?= admin_order_escape($notice) ?></div><?php endif; ?>
    <section class="order-summary-grid" aria-label="Order totals">
        <article class="order-summary-card"><span><i class="bi bi-cart3"></i> Total orders</span><strong><?= $totalOrders ?></strong></article>
        <article class="order-summary-card is-pending"><span><i class="bi bi-clock"></i> Pending</span><strong><?= $counts['Pending'] ?></strong></article>
        <article class="order-summary-card is-accepted"><span><i class="bi bi-patch-check-fill"></i> Accepted</span><strong><?= $counts['Accepted'] ?></strong></article>
        <article class="order-summary-card is-denied"><span><i class="bi bi-x-octagon-fill"></i> Declined</span><strong><?= $counts['Denied'] ?></strong></article>
    </section>

    <?php foreach ($sections as $sectionId => $section):
        $sourceOrders = $section['type'] === 'all' ? $allOrders : $ordersByType[$section['type']];
        $visibleOrders = array_values(array_filter($sourceOrders, static fn($row) => strcasecmp((string)($row['status'] ?? ''), $section['status']) === 0));
    ?>
    <section class="orders-section">
        <header class="orders-section-heading"><div><span class="orders-section-icon"><i class="bi <?= admin_order_escape($section['icon']) ?>"></i></span><div><h2><?= admin_order_escape($section['title']) ?></h2><p><?= count($visibleOrders) ?> <?= count($visibleOrders) === 1 ? 'order' : 'orders' ?></p></div></div><button type="button" class="orders-collapse" aria-label="Toggle <?= admin_order_escape($section['title']) ?>" aria-expanded="true" data-collapse-target="<?= admin_order_escape($sectionId) ?>"><i class="bi bi-chevron-up"></i></button></header>
        <div class="orders-scroll-list" id="<?= admin_order_escape($sectionId) ?>">
            <?php if (!$visibleOrders): ?><div class="orders-empty">No <?= strtolower(admin_order_escape($section['status'])) ?> orders here.</div><?php endif; ?>
            <?php foreach ($visibleOrders as $order):
                $userId = (int)($order['user_id'] ?? 0);
                $userLookup->bind_param('i', $userId);
                $userLookup->execute();
                $customer = $userLookup->get_result()->fetch_assoc() ?: [];
                $orderId = (int)($order['order_id'] ?? $order['_payment_id']);
                $deliveryName = '';
                $deliveryAddress = (string)($order['delivery_address'] ?? '');
                $publicOrderNumber = '';
                if ($orderInfoLookup && $orderId > 0) {
                    $orderInfoLookup->bind_param('ii', $orderId, $userId);
                    $orderInfoLookup->execute();
                    $orderInfo = $orderInfoLookup->get_result()->fetch_assoc() ?: [];
                    $deliveryName = (string)($orderInfo['delivery_name'] ?? '');
                    if ($deliveryAddress === '') $deliveryAddress = (string)($orderInfo['delivery_address'] ?? '');
                    $publicOrderNumber = (string)($orderInfo['public_order_number'] ?? '');
                    if ($publicOrderNumber === '') {
                        $publicOrderNumber = strtoupper(bin2hex(random_bytes(10)));
                        $saveOrderNumber = $conn->prepare("UPDATE orders SET public_order_number = ? WHERE order_id = ? AND user_id = ? AND (public_order_number IS NULL OR public_order_number = '')");
                        if ($saveOrderNumber) {
                            $saveOrderNumber->bind_param('sii', $publicOrderNumber, $orderId, $userId);
                            $saveOrderNumber->execute();
                            $saveOrderNumber->close();
                        }
                    }
                }
                $customerName = $deliveryName ?: (string)($customer['name'] ?? ('Customer #' . $userId));
                $items = admin_order_products($productLookup, (string)($order['furniture_name'] ?? ''));
                $statusClass = strtolower((string)$order['status']);
                $paymentId = (int)$order['_payment_id'];
                $type = (string)$order['_type'];
            ?>
            <article class="admin-order-card">
                <header class="admin-order-card-head"><div><span class="admin-order-number"><?= $type === 'delivery' ? 'Delivery' : 'In-store' ?> order · <?= admin_order_escape($publicOrderNumber !== '' ? $publicOrderNumber : 'Order reference unavailable') ?></span><span class="admin-order-date"><i class="bi bi-calendar3"></i> <?= admin_order_escape(!empty($order['created_at']) ? date('M j, Y · g:i A', strtotime((string)$order['created_at'])) : 'Date unavailable') ?></span></div><span class="admin-order-status is-<?= admin_order_escape($statusClass) ?>"><?= admin_order_escape($order['status']) ?></span></header>
                <div class="admin-order-customer"><span class="admin-customer-avatar"><i class="bi bi-person"></i></span><div class="admin-customer-main"><strong><?= admin_order_escape($customerName) ?></strong><span>User #<?= $userId ?><?= !empty($customer['email']) ? ' · ' . admin_order_escape($customer['email']) : '' ?></span><small><?= admin_order_escape($deliveryAddress !== '' ? $deliveryAddress : ($type === 'instore' ? 'In-store pickup' : 'Delivery address not provided')) ?></small></div><div class="admin-order-total"><span>Order total</span><strong><?= admin_order_money($order['total_amount'] ?? 0) ?></strong></div></div>
                <div class="admin-order-items">
                    <?php foreach ($items as $item):
                        $image = trim((string)$item['image']);
                        $imageUrl = $image === '' ? '' : app_url((str_starts_with($image, 'uploads/') || str_starts_with($image, 'pictures/') ? $image : 'uploads/' . ltrim($image, '/')));
                    ?>
                    <div class="admin-order-product">
                        <?php if ($imageUrl !== ''): ?><img src="<?= admin_order_escape($imageUrl) ?>" alt="<?= admin_order_escape($item['name']) ?>"><?php else: ?><span class="admin-order-product-placeholder"><i class="bi bi-box"></i></span><?php endif; ?>
                        <div class="admin-order-product-copy"><strong title="<?= admin_order_escape($item['name']) ?>"><?= admin_order_escape($item['name']) ?></strong><span title="<?= admin_order_escape($item['description']) ?>"><?= admin_order_escape($item['description'] ?: 'Furniture item') ?></span><small>Qty <?= (int)$item['quantity'] ?> · <?= admin_order_money($item['price']) ?> each</small></div>
                        <strong class="admin-order-product-total"><?= admin_order_money($item['price'] * $item['quantity']) ?></strong>
                    </div>
                    <?php endforeach; ?>
                </div>
                <footer class="admin-order-card-foot"><span><i class="bi <?= $type === 'delivery' ? 'bi-truck' : 'bi-shop' ?>"></i> <?= $type === 'delivery' ? 'Delivery' : 'In-store pickup' ?></span>
                    <?php if (strcasecmp((string)$order['status'], 'Pending') === 0): ?>
                    <div class="admin-order-actions"><form method="post"><input type="hidden" name="csrf_token" value="<?= admin_order_escape($csrfToken) ?>"><input type="hidden" name="payment_id" value="<?= $paymentId ?>"><input type="hidden" name="payment_type" value="<?= admin_order_escape($type) ?>"><button class="admin-order-action is-accept" name="order_action" value="accept"><i class="bi bi-check-lg"></i> Approve</button></form><form method="post"><input type="hidden" name="csrf_token" value="<?= admin_order_escape($csrfToken) ?>"><input type="hidden" name="payment_id" value="<?= $paymentId ?>"><input type="hidden" name="payment_type" value="<?= admin_order_escape($type) ?>"><button class="admin-order-action is-decline" name="order_action" value="deny"><i class="bi bi-x-lg"></i> Decline</button></form></div>
                    <?php else: ?><span class="admin-order-closed-label">Order <?= admin_order_escape(strtolower((string)$order['status'])) ?></span><?php endif; ?>
                </footer>
            </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endforeach; ?>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('[data-collapse-target]').forEach(button => button.addEventListener('click', () => {
    const list = document.getElementById(button.dataset.collapseTarget);
    const expanded = button.getAttribute('aria-expanded') === 'true';
    list.hidden = expanded;
    button.setAttribute('aria-expanded', String(!expanded));
    button.innerHTML = `<i class="bi ${expanded ? 'bi-chevron-down' : 'bi-chevron-up'}"></i>`;
}));
</script>
</body>
</html>

