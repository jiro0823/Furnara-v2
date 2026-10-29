<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';

if (empty($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    header('Location: ' . app_url('pages/auth/login.php'));
    exit;
}
if (empty($_SESSION['delivery_tracking_csrf'])) {
    $_SESSION['delivery_tracking_csrf'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['delivery_tracking_csrf'];
$notice = '';
$allowedStatuses = ['Preparing', 'Out for Delivery', 'Delivered'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $trackingId = filter_var($_POST['tracking_id'] ?? null, FILTER_VALIDATE_INT);
    $newStatus = trim((string)($_POST['new_status'] ?? ''));
    $postedToken = (string)($_POST['csrf_token'] ?? '');
    if (!hash_equals($csrfToken, $postedToken)) {
        $notice = 'Your session expired. Refresh the page and try again.';
    } elseif (!$trackingId || !in_array($newStatus, $allowedStatuses, true)) {
        $notice = 'That delivery update is invalid.';
    } else {
        $update = $conn->prepare("UPDATE delivery_tracking dt INNER JOIN orders o ON o.order_id = dt.order_id AND o.user_id = dt.user_id SET dt.status = ? WHERE dt.tracking_id = ? AND LOWER(o.status) = 'accepted'");
        if ($update) {
            $update->bind_param('si', $newStatus, $trackingId);
            $update->execute();
            $notice = $update->affected_rows > 0 ? 'Delivery status updated.' : 'No change was made. The order may not be accepted or already has that status.';
            $update->close();
        } else {
            error_log('Delivery status update prepare failed: ' . $conn->error);
            $notice = 'The delivery status could not be updated.';
        }
    }
}

$trackingOrders = [];
$query = "SELECT dt.tracking_id, dt.order_id, dt.user_id, dt.customer_name AS tracking_customer_name,
                 dt.delivery_address AS tracking_address, dt.status AS tracking_status, dt.estimated_delivery,
                 o.public_order_number, o.furniture_name, o.total_amount, o.total_price, o.delivery_type,
                 o.delivery_fee, o.delivery_name, o.delivery_address, o.order_date,
                 u.name AS account_name, u.email
          FROM delivery_tracking dt
          INNER JOIN orders o ON o.order_id = dt.order_id AND o.user_id = dt.user_id
          LEFT JOIN users u ON u.id = dt.user_id
          WHERE LOWER(o.status) = 'accepted'
          ORDER BY CASE WHEN LOWER(dt.status) = 'delivered' THEN 1 ELSE 0 END, dt.tracking_id DESC";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if (empty($row['public_order_number'])) {
            $publicNumber = strtoupper(bin2hex(random_bytes(10)));
            $saveNumber = $conn->prepare("UPDATE orders SET public_order_number = ? WHERE order_id = ? AND user_id = ? AND (public_order_number IS NULL OR public_order_number = '')");
            if ($saveNumber) {
                $saveNumber->bind_param('sii', $publicNumber, $row['order_id'], $row['user_id']);
                $saveNumber->execute();
                $saveNumber->close();
                $row['public_order_number'] = $publicNumber;
            }
        }
        $trackingOrders[] = $row;
    }
} else {
    error_log('Delivery tracking query failed: ' . $conn->error);
}

$productLookup = $conn->prepare('SELECT name, description, image, price FROM furniture WHERE LOWER(name) = LOWER(?) LIMIT 1');
function tracking_escape($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function tracking_money($value): string { return '&#8369;' . number_format((float)$value, 2); }
function tracking_products(mysqli_stmt $lookup, string $raw): array {
    $items = [];
    foreach (preg_split('/\s*,\s*/', trim($raw), -1, PREG_SPLIT_NO_EMPTY) as $part) {
        $name = trim($part);
        $quantity = 1;
        if (preg_match('/^(.*?)\s+x(\d+)$/i', $part, $match)) {
            $name = trim($match[1]);
            $quantity = max(1, (int)$match[2]);
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

$statusGroups = [
    1 => ['title' => 'Preparing', 'description' => 'Orders being prepared for dispatch.', 'icon' => 'bi-box2'],
    2 => ['title' => 'Out for delivery', 'description' => 'Orders currently with the delivery team.', 'icon' => 'bi-truck'],
    3 => ['title' => 'Fully delivered', 'description' => 'Completed deliveries.', 'icon' => 'bi-check2-circle'],
];
$ordersByStage = [1 => [], 2 => [], 3 => []];
foreach ($trackingOrders as $trackingOrder) {
    $normalizedStatus = strtolower((string)$trackingOrder['tracking_status']);
    $orderStage = $normalizedStatus === 'delivered' ? 3 : (str_contains($normalizedStatus, 'out') ? 2 : 1);
    $ordersByStage[$orderStage][] = $trackingOrder;
}
$statusCounts = [
    'Preparing' => count($ordersByStage[1]),
    'Out for Delivery' => count($ordersByStage[2]),
    'Delivered' => count($ordersByStage[3]),
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Delivery Tracking | Furnara Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= app_url('assets/css/admin_style.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/admin_sidebar.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/admin_tracking.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/support_chat_widget.css') ?>">
</head>
<body class="admin-tracking-page">
<?php require dirname(__DIR__, 2) . '/components/navigation/admin_sidebar.php'; ?>
<main class="main-content admin-tracking-main">
    <header class="tracking-heading"><div><p class="tracking-eyebrow">FULFILLMENT / DELIVERY</p><h1><i class="bi bi-truck"></i> Delivery tracking</h1><p>Follow accepted deliveries and update their progress.</p></div></header>
    <?php if ($notice !== ''): ?><div class="tracking-notice" role="status"><i class="bi bi-info-circle"></i><?= tracking_escape($notice) ?></div><?php endif; ?>
    <section class="tracking-summary" aria-label="Delivery status summary">
        <article><span><i class="bi bi-box-seam"></i> Active deliveries</span><strong><?= count($trackingOrders) - $statusCounts['Delivered'] ?></strong></article>
        <article class="is-preparing"><span><i class="bi bi-box2"></i> Preparing</span><strong><?= $statusCounts['Preparing'] ?></strong></article>
        <article class="is-transit"><span><i class="bi bi-truck"></i> Out for delivery</span><strong><?= $statusCounts['Out for Delivery'] ?></strong></article>
        <article class="is-delivered"><span><i class="bi bi-check2-circle"></i> Delivered</span><strong><?= $statusCounts['Delivered'] ?></strong></article>
    </section>
    <?php foreach ($statusGroups as $groupStage => $group): ?>
    <section class="tracking-board tracking-stage-board stage-group-<?= $groupStage ?>">
        <header class="tracking-board-heading"><div class="tracking-stage-title"><span class="tracking-stage-icon"><i class="bi <?= tracking_escape($group['icon']) ?>"></i></span><div><h2><?= tracking_escape($group['title']) ?></h2><p><?= count($ordersByStage[$groupStage]) ?> <?= count($ordersByStage[$groupStage]) === 1 ? 'order' : 'orders' ?> · <?= tracking_escape($group['description']) ?></p></div></div></header>
        <div class="tracking-card-list">
            <?php if (!$ordersByStage[$groupStage]): ?><div class="tracking-empty"><i class="bi <?= tracking_escape($group['icon']) ?>"></i><strong>No <?= tracking_escape(strtolower($group['title'])) ?> orders</strong><span>Orders will appear here when they reach this status.</span></div><?php endif; ?>
            <?php foreach ($ordersByStage[$groupStage] as $tracking):
                $status = (string)($tracking['tracking_status'] ?? 'Preparing');
                $statusLower = strtolower($status);
                $stage = $statusLower === 'delivered' ? 3 : (str_contains($statusLower, 'out') ? 2 : 1);
                $customerName = (string)($tracking['delivery_name'] ?: ($tracking['tracking_customer_name'] ?: ($tracking['account_name'] ?: 'Customer')));
                $address = (string)($tracking['delivery_address'] ?: $tracking['tracking_address']);
                $orderDate = $tracking['order_date'] ?? null;
                $items = tracking_products($productLookup, (string)$tracking['furniture_name']);
                $deliveryType = strtolower((string)($tracking['delivery_type'] ?? 'delivery'));
                $isCash = str_contains($deliveryType, 'cash') || str_contains($deliveryType, 'cod');
                $total = (float)($tracking['total_amount'] ?? $tracking['total_price'] ?? 0);
                $fee = (float)($tracking['delivery_fee'] ?? 0);
                $expected = !empty($tracking['estimated_delivery']) ? date('M j, Y · g:i A', strtotime((string)$tracking['estimated_delivery'])) : 'To be confirmed';
            ?>
            <article class="tracking-order-card">
                <header class="tracking-order-head"><div><span class="tracking-order-ref">Order <?= tracking_escape($tracking['public_order_number']) ?></span><small><?= tracking_escape($orderDate ? date('M j, Y · g:i A', strtotime((string)$orderDate)) : 'Order date unavailable') ?> · Tracking #<?= (int)$tracking['tracking_id'] ?></small></div><span class="tracking-status-pill stage-<?= $stage ?>"><?= tracking_escape($status) ?></span></header>
                <div class="tracking-progress" aria-label="Delivery progress: <?= tracking_escape($status) ?>"><div class="tracking-progress-line"><span style="width:<?= $stage === 1 ? '33%' : ($stage === 2 ? '66%' : '100%') ?>"></span></div><?php foreach (['Order placed', 'Preparing', 'Out for delivery', 'Delivered'] as $index => $label): $step = $index + 1; $complete = $step === 1 || $stage >= ($step - 1); $current = $stage === ($step - 1); ?><div class="tracking-progress-step <?= $complete ? 'is-complete' : '' ?> <?= $current ? 'is-current' : '' ?>"><i class="bi <?= $complete ? 'bi-check-lg' : 'bi-circle' ?>"></i><span><?= tracking_escape($label) ?></span></div><?php endforeach; ?></div>
                <div class="tracking-recipient"><span class="tracking-recipient-icon"><i class="bi bi-geo-alt"></i></span><div><strong><?= tracking_escape($customerName) ?></strong><small><?= tracking_escape($tracking['email'] ?? '') ?><?= $address !== '' ? ' · ' . tracking_escape($address) : ' · Delivery address not provided' ?></small></div><div class="tracking-arrival"><span>Estimated arrival</span><strong><?= tracking_escape($expected) ?></strong></div></div>
                <div class="tracking-products">
                    <?php foreach ($items as $item):
                        $image = trim((string)$item['image']);
                        $imageUrl = $image === '' ? '' : app_url((str_starts_with($image, 'uploads/') || str_starts_with($image, 'pictures/') ? $image : 'uploads/' . ltrim($image, '/')));
                    ?>
                    <div class="tracking-product-row"><?php if ($imageUrl): ?><img src="<?= tracking_escape($imageUrl) ?>" alt="<?= tracking_escape($item['name']) ?>"><?php else: ?><span class="tracking-product-placeholder"><i class="bi bi-box"></i></span><?php endif; ?><div class="tracking-product-info"><strong title="<?= tracking_escape($item['name']) ?>"><?= tracking_escape($item['name']) ?></strong><span title="<?= tracking_escape($item['description']) ?>"><?= tracking_escape($item['description'] ?: 'Furniture item') ?></span><small>Qty <?= (int)$item['quantity'] ?></small></div><strong class="tracking-product-price"><?= tracking_money($item['price'] * $item['quantity']) ?></strong></div>
                    <?php endforeach; ?>
                </div>
                <div class="tracking-order-summary"><div><span>Payment</span><strong><i class="bi <?= $isCash ? 'bi-cash-coin' : 'bi-credit-card' ?>"></i> <?= $isCash ? 'Cash on delivery' : tracking_escape(ucwords($deliveryType ?: 'Paid')) ?></strong></div><div><span>Delivery fee</span><strong><?= tracking_money($fee) ?></strong></div><div class="tracking-grand-total"><span>Total</span><strong><?= tracking_money($total) ?></strong></div></div>
                <footer class="tracking-card-actions"><span>Update delivery status</span><form method="post" class="tracking-status-actions"><input type="hidden" name="csrf_token" value="<?= tracking_escape($csrfToken) ?>"><input type="hidden" name="tracking_id" value="<?= (int)$tracking['tracking_id'] ?>"><button name="new_status" value="Preparing" class="<?= $stage === 1 ? 'is-current' : '' ?>" <?= $stage >= 1 ? 'disabled' : '' ?>><i class="bi bi-box2"></i> Preparing</button><button name="new_status" value="Out for Delivery" class="<?= $stage === 2 ? 'is-current' : '' ?>" <?= $stage >= 2 ? 'disabled' : '' ?>><i class="bi bi-truck"></i> Out for delivery</button><button name="new_status" value="Delivered" class="<?= $stage === 3 ? 'is-current' : '' ?>" <?= $stage >= 3 ? 'disabled' : '' ?>><i class="bi bi-check2-circle"></i> Delivered</button><input type="hidden" name="update_status" value="1"></form></footer>
            </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endforeach; ?>
</main>
</body>
</html>

