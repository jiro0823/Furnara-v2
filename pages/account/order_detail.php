<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ' . app_url('pages/auth/login.php'));
    exit;
}
$userId = (int)$_SESSION['user_id'];
$isDrawerView = ($_GET['drawer'] ?? '') === '1';
if (empty($_SESSION['account_csrf_token'])) $_SESSION['account_csrf_token'] = bin2hex(random_bytes(32));
$csrfToken = $_SESSION['account_csrf_token'];
$requestedOrderNumber = strtoupper(trim((string)($_GET['order'] ?? '')));
if (!preg_match('/^[A-F0-9]{20}$/', $requestedOrderNumber)) {
    header('Location: ' . app_url('pages/account/Orders.php' . (($_GET['drawer'] ?? '') === '1' ? '?drawer=1' : '')));
    exit;
}

$sql = "SELECT o.*,
    (SELECT dp.delivery_date FROM delivery_payment dp WHERE dp.order_id=o.order_id AND dp.user_id=o.user_id ORDER BY dp.id DESC LIMIT 1) AS delivery_date,
    (SELECT dp.delivery_time FROM delivery_payment dp WHERE dp.order_id=o.order_id AND dp.user_id=o.user_id ORDER BY dp.id DESC LIMIT 1) AS delivery_time,
    (SELECT dp.delivery_address FROM delivery_payment dp WHERE dp.order_id=o.order_id AND dp.user_id=o.user_id ORDER BY dp.id DESC LIMIT 1) AS scheduled_address,
    (SELECT dp.status FROM delivery_payment dp WHERE dp.order_id=o.order_id AND dp.user_id=o.user_id ORDER BY dp.id DESC LIMIT 1) AS payment_status,
    (SELECT dt.status FROM delivery_tracking dt WHERE dt.order_id=o.order_id AND dt.user_id=o.user_id ORDER BY dt.tracking_id DESC LIMIT 1) AS tracking_status,
    (SELECT dt.estimated_delivery FROM delivery_tracking dt WHERE dt.order_id=o.order_id AND dt.user_id=o.user_id ORDER BY dt.tracking_id DESC LIMIT 1) AS estimated_delivery
    FROM orders o WHERE o.public_order_number=? AND o.user_id=? LIMIT 1";
$statement = $conn->prepare($sql);
$statement->bind_param('si', $requestedOrderNumber, $userId);
$statement->execute();
$order = $statement->get_result()->fetch_assoc();
$statement->close();
if (!$order) {
    http_response_code(404);
    exit('Order not found.');
}
$orderId = (int)$order['order_id'];

if (empty($order['public_order_number'])) {
    $reference = strtoupper(bin2hex(random_bytes(10)));
    $saveReference = $conn->prepare("UPDATE orders SET public_order_number=? WHERE order_id=? AND user_id=? AND (public_order_number IS NULL OR public_order_number='')");
    $saveReference->bind_param('sii', $reference, $orderId, $userId);
    $saveReference->execute();
    $saveReference->close();
    $order['public_order_number'] = $reference;
}
$status = (string)($order['tracking_status'] ?: ($order['payment_status'] ?: $order['status']));
$statusLower = strtolower($status);
$isDelivery = strtolower((string)$order['delivery_type']) === 'delivery';
$storeLocation = null;
if ($isDelivery) {
    $storeLocationResult = $conn->query("SELECT setting_value FROM site_settings WHERE setting_key = 'store_location' LIMIT 1");
    if ($storeLocationResult && ($storeLocationRow = $storeLocationResult->fetch_assoc())) $storeLocation = json_decode((string)$storeLocationRow['setting_value'], true);
}
$isDelivered = in_array($statusLower, ['delivered', 'received', 'completed'], true);
$isCancelled = str_contains($statusLower, 'cancel');
$storedTotal = (float)($order['total_amount'] ?: $order['total_price']);
$deliveryFee = (float)($order['delivery_fee'] ?? 0);
if ($isDelivery && $deliveryFee <= 0) $deliveryFee = 100;
$displayTotal = $deliveryFee > 0 && (float)($order['delivery_fee'] ?? 0) > 0
    ? $storedTotal
    : $storedTotal + ($isDelivery ? 100 : 0);
$displaySubtotal = max(0, $displayTotal - $deliveryFee);
$expectedDate = $isDelivery ? ($order['delivery_date'] ?: $order['estimated_delivery']) : $order['pickup_date'];
$expectedTime = $isDelivery ? $order['delivery_time'] : $order['pickup_time'];
$expectedDateText = (string)($expectedDate ?? '');
if (!$expectedTime && $expectedDateText !== '' && preg_match('/\d{2}:\d{2}/', $expectedDateText)) $expectedTime = date('H:i:s', strtotime($expectedDateText));
$expectedTimestamp = $expectedDateText !== '' ? strtotime(substr($expectedDateText, 0, 10) . ($expectedTime ? ' ' . $expectedTime : '')) : false;
$expectedLabel = $expectedTimestamp ? date('M j, Y', $expectedTimestamp) . ($expectedTime ? ' · ' . date('g:i A', $expectedTimestamp) : '') : 'To be confirmed';
$recipient = $isDelivery ? (string)($order['delivery_name'] ?: ($_SESSION['user_name'] ?? '')) : (string)($_SESSION['user_name'] ?? '');
$address = $isDelivery ? (string)($order['scheduled_address'] ?: $order['delivery_address'] ?: '') : 'In-store pickup';
$contact = $isDelivery ? (string)($order['delivery_contact'] ?? '') : '';
$step = 0;
if ($isDelivered) $step = 3;
elseif (str_contains($statusLower, 'transit') || str_contains($statusLower, 'out for delivery') || str_contains($statusLower, 'ship')) $step = 2;
elseif (str_contains($statusLower, 'prepar') || str_contains($statusLower, 'accept') || str_contains($statusLower, 'courier')) $step = 1;
$products = [];
$productLookup = $conn->prepare('SELECT furniture_id,name,description,image,price FROM furniture WHERE LOWER(name)=LOWER(?) LIMIT 1');
foreach (preg_split('/\s*,\s*/', (string)$order['furniture_name'], -1, PREG_SPLIT_NO_EMPTY) as $part) {
    $name = trim($part);
    $quantity = 1;
    if (preg_match('/^(.*?)\s+x(\d+)$/i', $part, $match)) {
        $name = trim($match[1]);
        $quantity = max(1, (int)$match[2]);
    }
    $productLookup->bind_param('s', $name);
    $productLookup->execute();
    $product = $productLookup->get_result()->fetch_assoc() ?: ['furniture_id'=>0,'name'=>$name,'description'=>'','image'=>'','price'=>0];
    $image = (string)($product['image'] ?? '');
    $product['image_url'] = $image !== '' && is_file(APP_ROOT . '/uploads/' . $image)
        ? app_url('uploads/' . rawurlencode($image))
        : app_url('pictures/bg-chair.png');
    $product['quantity'] = $quantity;
    $products[] = $product;
}
$productLookup->close();
function detail_h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function detail_money($value): string { return '₱' . number_format((float)$value, 2); }
$stepLabels = ['Order placed', 'Preparing', $isDelivery ? 'In transit' : 'Ready for pickup', $isDelivery ? 'Delivered' : 'Picked up'];
$descriptions = [
    'Order placed' => 'We have received your order.',
    'Pending' => 'Your order is waiting for confirmation.',
    'Accepted' => 'The store has accepted your order and is preparing it.',
    'Preparing' => 'Your order is being prepared for dispatch.',
    'Out for Delivery' => 'Your package is with the courier and on its way.',
    'Delivered' => 'Your order has been marked delivered.',
    'Cancelled' => 'This order was cancelled.',
];
$statusDescription = $descriptions[$status] ?? 'We will update this status as your order progresses.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order <?= detail_h($order['public_order_number']) ?> | Furnara</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= app_url('assets/css/user_sidebar.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/account_pages.css') ?>">
    <?php if ($isDrawerView): ?><link rel="stylesheet" href="<?= app_url('assets/css/account_drawer_embedded.css') ?>"><?php endif; ?>
    <link rel="stylesheet" href="<?= app_url('assets/css/support_chat_widget.css') ?>">
    <?php if ($isDelivery): ?><link rel="stylesheet" href="https://unpkg.com/maplibre-gl@5.6.2/dist/maplibre-gl.css"><?php endif; ?>
</head>
<body class="account-page<?= $isDrawerView ? ' account-embedded' : '' ?>">
<div class="account-shell">
    <?php if (!$isDrawerView) require dirname(__DIR__, 2) . '/components/navigation/user_sidebar.php'; ?>
    <main class="account-main">
        <div class="account-content order-detail-content">
            <a class="order-back-link" href="<?= app_url('pages/account/Orders.php' . ($isDrawerView ? '?drawer=1' : '')) ?>"><i class="bi bi-arrow-left"></i> All orders</a>
            <header class="order-detail-heading"><div><p class="account-eyebrow">Order details</p><h1 class="account-page-title"><?= $isDelivery ? 'Delivery order' : 'In-store pickup' ?></h1><p class="account-page-subtitle mb-0">Order number <strong><?= detail_h($order['public_order_number']) ?></strong></p></div><span class="order-status-pill"><?= detail_h($status) ?></span></header>

            <section class="account-card order-detail-status">
                <div class="order-progress <?= $isCancelled ? 'is-cancelled' : '' ?>">
                    <?php foreach ($stepLabels as $index => $label): ?>
                        <div class="order-progress-step <?= !$isCancelled && $index < $step ? 'is-complete' : '' ?> <?= !$isCancelled && $index === $step ? 'is-current' : '' ?>">
                            <span class="order-progress-dot"><?= $index < $step && !$isCancelled ? '<i class="bi bi-check-lg"></i>' : ($index + 1) ?></span><span><?= detail_h($label) ?></span>
                        </div>
                    <?php endforeach; ?>
                    <div class="order-progress-track"><span style="width:<?= $isCancelled ? 0 : (int)($step / 3 * 100) ?>%"></span></div>
                </div>
                <div class="order-current-update"><i class="bi <?= $isDelivered ? 'bi-check-circle-fill' : ($isCancelled ? 'bi-x-circle-fill' : 'bi-box-seam') ?>"></i><div><strong><?= detail_h($status) ?></strong><p><?= detail_h($statusDescription) ?> · Order placed <?= !empty($order['order_date']) ? detail_h(date('M j, Y · g:i A', strtotime((string)$order['order_date']))) : '' ?></p></div><i class="bi bi-chevron-right ms-auto"></i></div>
                <div class="order-guarantee"><i class="bi bi-stopwatch"></i><p><strong><?= $isDelivery ? 'Delivery commitment' : 'Pickup schedule' ?></strong><br><?= $isDelivery ? 'Our target is to attempt delivery by ' : 'Your order is scheduled for ' ?><strong><?= detail_h($expectedLabel) ?></strong>. We will show the latest status here if the schedule changes.</p></div>
                <div class="order-recipient"><i class="bi bi-geo-alt-fill"></i><div><strong><?= detail_h($recipient ?: 'Customer') ?></strong><?= $contact ? ' <span>' . detail_h($contact) . '</span>' : '' ?><p><?= detail_h($address ?: 'Address not provided') ?></p></div></div>
            </section>

            <?php if ($isDelivery): ?>
                <section class="account-card order-route-card"><div class="order-detail-section-heading"><div><p class="account-eyebrow">Delivery route</p><h2>From Furnara to your address</h2><p>The route follows the saved delivery pin for this order.</p></div><i class="bi bi-sign-turn-right"></i></div>
                <?php if ($storeLocation && isset($order['delivery_latitude'], $order['delivery_longitude']) && $order['delivery_latitude'] !== null && $order['delivery_longitude'] !== null): ?>
                    <div class="delivery-route-map" data-delivery-route-map data-origin-lat="<?= detail_h($storeLocation['latitude'] ?? '') ?>" data-origin-lng="<?= detail_h($storeLocation['longitude'] ?? '') ?>" data-destination-lat="<?= detail_h($order['delivery_latitude']) ?>" data-destination-lng="<?= detail_h($order['delivery_longitude']) ?>" aria-label="Animated delivery route map"></div>
                    <small class="text-secondary">Map © CARTO · Route © OSRM · Address data © OpenStreetMap contributors</small>
                <?php else: ?><p class="mb-0 text-secondary">The store or delivery map pin is not available for this order yet.</p><?php endif; ?></section>
            <?php endif; ?>

            <section class="account-card order-detail-products">
                <header class="order-detail-section-heading"><div><p class="account-eyebrow">Furnara order</p><h2>Items in this order</h2></div><span><?= count($products) ?> item<?= count($products) === 1 ? '' : 's' ?></span></header>
                <?php foreach ($products as $product): ?>
                    <article class="order-detail-product">
                        <img src="<?= detail_h($product['image_url']) ?>" alt="<?= detail_h($product['name']) ?>">
                        <div class="order-detail-product-info"><h3><?= detail_h($product['name']) ?></h3><p>Default</p><span><?= detail_h(mb_strimwidth((string)$product['description'], 0, 100, '…')) ?></span></div>
                        <div class="order-detail-product-price"><strong><?= detail_money((float)$product['price'] * (int)$product['quantity']) ?></strong><span>× <?= (int)$product['quantity'] ?></span></div>
                    </article>
                <?php endforeach; ?>
                <div class="order-reference-panel"><div class="order-reference-row"><span>Order number</span><strong><?= detail_h($order['public_order_number']) ?></strong><button type="button" data-copy-order="<?= detail_h($order['public_order_number']) ?>" aria-label="Copy order number"><i class="bi bi-copy"></i></button></div>
                    <div class="order-payment-row"><span><i class="bi <?= $isDelivery ? 'bi-truck' : 'bi-cash-coin' ?>"></i> <?= $isDelivery ? 'Cash on delivery' : 'Cash at pickup' ?></span><strong><?= detail_money($displayTotal) ?></strong></div>
                    <div class="order-fee-breakdown"><span>Items subtotal</span><span><?= detail_money($displaySubtotal) ?></span></div>
                    <div class="order-fee-breakdown"><span><?= $isDelivery ? 'Delivery fee' : 'Pickup fee' ?></span><span><?= detail_money($deliveryFee) ?></span></div>
                </div>
            </section>

            <?php if (!$isDelivered && !$isCancelled): ?>
                <form class="order-detail-cancel" method="post" action="<?= app_url('pages/orders/cancel_order.php') ?>" onsubmit="return confirm('Cancel this order?')">
                    <input type="hidden" name="csrf_token" value="<?= detail_h($csrfToken) ?>"><input type="hidden" name="order_number" value="<?= detail_h($order['public_order_number']) ?>"><input type="hidden" name="drawer" value="<?= $isDrawerView ? '1' : '0' ?>">
                    <button class="order-action-button" type="submit">Cancel order</button>
                </form>
            <?php endif; ?>

        </div>
    </main>
</div>
<script>
document.querySelector('[data-copy-order]')?.addEventListener('click', async event => {
    const value = event.currentTarget.dataset.copyOrder;
    try { await navigator.clipboard.writeText(value); event.currentTarget.innerHTML = '<i class="bi bi-check2"></i>'; }
    catch (_) { window.prompt('Copy your order number:', value); }
});
</script>
<?php if ($isDelivery): ?><script src="https://unpkg.com/maplibre-gl@5.6.2/dist/maplibre-gl.js"></script><script src="<?= app_url('assets/js/location_maps.js') ?>"></script><?php endif; ?>
</body>
</html>
