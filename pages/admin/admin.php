<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';

if (empty($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    header('Location: ' . app_url('pages/auth/login.php'));
    exit;
}

if (empty($_SESSION['product_form_token'])) {
    $_SESSION['product_form_token'] = bin2hex(random_bytes(32));
}
$addProductToken = $_SESSION['product_form_token'];
$refundActionToken = $_SESSION['refund_action_token'] ??= bin2hex(random_bytes(32));
$storeLocationToken = $_SESSION['store_location_csrf'] ??= bin2hex(random_bytes(32));
$addProductErrors = $_SESSION['product_form_errors'] ?? [];
$addProductValues = $_SESSION['product_form_values'] ?? [];
unset($_SESSION['product_form_errors'], $_SESSION['product_form_values']);
$openAddProductModal = ($_GET['add_product'] ?? '') === '1' || !empty($addProductErrors);
$productAddedNotice = ($_GET['product_added'] ?? '') === '1';
$addedProductName = trim((string)($_GET['product'] ?? ''));
$storeLocation = null;
$storeLocationResult = $conn->query("SELECT setting_value FROM site_settings WHERE setting_key = 'store_location' LIMIT 1");
if ($storeLocationResult && ($storeLocationRow = $storeLocationResult->fetch_assoc())) $storeLocation = json_decode((string)$storeLocationRow['setting_value'], true);

function dashboard_scalar(mysqli $conn, string $sql, string $key = 'value', $fallback = 0)
{
    $result = mysqli_query($conn, $sql);
    if (!$result) return $fallback;
    $row = mysqli_fetch_assoc($result);
    return $row[$key] ?? $fallback;
}
function dashboard_rows(mysqli $conn, string $sql): array
{
    $result = mysqli_query($conn, $sql);
    if (!$result) return [];
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    return $rows;
}
function dashboard_money($amount): string
{
    return '&#8369;' . number_format((float)$amount, 2);
}
function dashboard_parse_items(string $itemList): array
{
    $items = [];
    foreach (preg_split('/,\s*/', $itemList) as $item) {
        $item = trim($item);
        if ($item === '') continue;
        if (preg_match('/^(.*?)\s+x(\d+)$/i', $item, $match)) {
            $name = trim($match[1]);
            $quantity = max(1, (int)$match[2]);
        } else {
            $name = $item;
            $quantity = 1;
        }
        $key = strtolower($name);
        $items[$key] = ($items[$key] ?? 0) + $quantity;
    }
    return $items;
}

$productCount = (int)dashboard_scalar($conn, 'SELECT COUNT(*) AS value FROM furniture');
$customerCount = (int)dashboard_scalar($conn, "SELECT COUNT(*) AS value FROM users WHERE user_type IS NULL OR LOWER(user_type) <> 'admin'");
$lowStockCount = (int)dashboard_scalar($conn, 'SELECT COUNT(*) AS value FROM furniture WHERE stock BETWEEN 1 AND 4');
$outOfStockCount = (int)dashboard_scalar($conn, 'SELECT COUNT(*) AS value FROM furniture WHERE stock <= 0');
$stockAlerts = dashboard_rows($conn, 'SELECT furniture_id, name, stock FROM furniture WHERE stock < 5 ORDER BY stock ASC, name ASC LIMIT 8');
$activeDiscountCount = (int)dashboard_scalar($conn, 'SELECT COUNT(*) AS value FROM furniture WHERE discount_percentage > 0 AND discount_start <= CURDATE() AND discount_end >= CURDATE()');
$scheduledDiscountCount = (int)dashboard_scalar($conn, 'SELECT COUNT(*) AS value FROM furniture WHERE discount_percentage > 0 AND discount_start > CURDATE()');
$expiredDiscountCount = (int)dashboard_scalar($conn, 'SELECT COUNT(*) AS value FROM furniture WHERE discount_percentage > 0 AND discount_end < CURDATE()');
$catalogProducts = dashboard_rows($conn, 'SELECT furniture_id, name, stock, price FROM furniture ORDER BY name ASC');
$productSales = [];
foreach ($catalogProducts as $catalogProduct) {
    $productKey = strtolower(trim((string)$catalogProduct['name']));
    $productSales[$productKey] = [
        'id' => (int)$catalogProduct['furniture_id'], 'name' => (string)$catalogProduct['name'],
        'units' => 0, 'orders' => 0, 'history' => [],
    ];
}

$paymentRows = dashboard_rows($conn, "
    SELECT 'In-store' AS channel, status, total_amount AS amount, created_at, furniture_name, user_id FROM instore_payment
    UNION ALL
    SELECT 'Delivery' AS channel, status, total_amount AS amount, created_at, furniture_name, user_id FROM delivery_payment
");
$orderStatusCounts = ['Pending' => 0, 'Accepted' => 0, 'Denied' => 0, 'Other' => 0];
$revenue = 0.0;
$pendingRevenue = 0.0;
$todayRevenue = 0.0;
$monthRevenue = 0.0;
$channelCounts = ['In-store' => 0, 'Delivery' => 0];
$deliveryOrderCount = 0;
$deliveryProductUnits = 0;
$recentOrders = [];
foreach ($paymentRows as $payment) {
    $status = ucfirst(strtolower(trim((string)$payment['status'])));
    if (isset($orderStatusCounts[$status])) $orderStatusCounts[$status]++;
    else $orderStatusCounts['Other']++;
    if (isset($channelCounts[$payment['channel']])) $channelCounts[$payment['channel']]++;
    $itemsInOrder = dashboard_parse_items((string)$payment['furniture_name']);
    if ($payment['channel'] === 'Delivery') {
        $deliveryOrderCount++;
        $deliveryProductUnits += array_sum($itemsInOrder);
    }
    $amount = (float)$payment['amount'];
    if ($status === 'Accepted') {
        $revenue += $amount;
        $createdAt = strtotime((string)$payment['created_at']);
        if ($createdAt && date('Y-m-d', $createdAt) === date('Y-m-d')) $todayRevenue += $amount;
        if ($createdAt && date('Y-m', $createdAt) === date('Y-m')) $monthRevenue += $amount;
        $saleMonth = $createdAt ? date('Y-m', $createdAt) : '';
        foreach ($itemsInOrder as $productKey => $quantity) {
            if (!isset($productSales[$productKey])) continue;
            $productSales[$productKey]['units'] += $quantity;
            $productSales[$productKey]['orders']++;
            if ($saleMonth !== '') $productSales[$productKey]['history'][$saleMonth] = ($productSales[$productKey]['history'][$saleMonth] ?? 0) + $quantity;
        }
    }
    if ($status === 'Pending') $pendingRevenue += $amount;
    $recentOrders[] = [
        'channel' => $payment['channel'], 'status' => $status, 'amount' => $amount,
        'created_at' => $payment['created_at'], 'item' => $payment['furniture_name'], 'user_id' => $payment['user_id'],
    ];
}
usort($recentOrders, static fn($a, $b) => strtotime((string)$b['created_at']) <=> strtotime((string)$a['created_at']));
$recentOrders = array_slice($recentOrders, 0, 6);
$totalOrders = count($paymentRows);

$deliveryRows = dashboard_rows($conn, 'SELECT status, COUNT(*) AS total FROM delivery_tracking GROUP BY status');
$deliveryLabels = [];
$deliveryValues = [];
foreach ($deliveryRows as $row) {
    $deliveryLabels[] = (string)$row['status'];
    $deliveryValues[] = (int)$row['total'];
}
$deliveryInTransit = (int)dashboard_scalar($conn, "SELECT COUNT(*) AS value FROM delivery_tracking WHERE LOWER(status) IN ('preparing', 'out for delivery')");
$deliveredCount = (int)dashboard_scalar($conn, "SELECT COUNT(*) AS value FROM delivery_tracking WHERE LOWER(status) = 'delivered'");

$revenueLabels = [];
$revenueValues = [];
$historyMonthKeys = [];
for ($offset = 5; $offset >= 0; $offset--) {
    $monthDate = new DateTimeImmutable('first day of this month');
    $monthDate = $monthDate->modify('-' . $offset . ' months');
    $start = $monthDate->format('Y-m-01');
    $end = $monthDate->modify('first day of next month')->format('Y-m-01');
    $revenueLabels[] = $monthDate->format('M');
    $historyMonthKeys[] = $monthDate->format('Y-m');
    $revenueValues[] = (float)dashboard_scalar($conn, "SELECT COALESCE(SUM(total_amount), 0) AS value FROM (SELECT total_amount, created_at, status FROM instore_payment UNION ALL SELECT total_amount, created_at, status FROM delivery_payment) payments WHERE LOWER(status) = 'accepted' AND created_at >= '$start' AND created_at < '$end'");
}
$productSalesList = array_values($productSales);
usort($productSalesList, static fn($a, $b) => $b['units'] <=> $a['units'] ?: strcasecmp($a['name'], $b['name']));
$bestSellingProduct = $productSalesList[0] ?? null;
$lowSellingProduct = $productSalesList ? $productSalesList[count($productSalesList) - 1] : null;
$leadersForHistory = array_values(array_filter(array_slice($productSalesList, 0, 5), static fn($product) => $product['units'] > 0));
$productHistoryDatasets = [];
foreach ($leadersForHistory as $index => $leader) {
    $productHistoryDatasets[] = [
        'label' => $leader['name'],
        'data' => array_map(static fn($month) => (int)($leader['history'][$month] ?? 0), $historyMonthKeys),
        'color' => ['#7355e8', '#4285f4', '#42c6c4', '#f3b44b', '#f16f75'][$index],
    ];
}
$productChartLabels = array_column($productSalesList, 'name');
$productChartValues = array_column($productSalesList, 'units');
$topProducts = dashboard_rows($conn, 'SELECT name, stock, price, discount_percentage FROM furniture ORDER BY stock ASC, name ASC LIMIT 5');
$allDiscounts = dashboard_rows($conn, "SELECT name, discount_percentage, discount_start, discount_end FROM furniture WHERE discount_percentage > 0 AND discount_end >= CURDATE() ORDER BY discount_start DESC LIMIT 5");
$refundRequests = dashboard_rows($conn, "SELECT rr.id, rr.order_id, rr.reason, rr.status, rr.created_at, u.name AS customer_name, f.name AS product_name FROM refund_requests rr LEFT JOIN users u ON u.id = rr.user_id LEFT JOIN furniture f ON f.furniture_id = rr.furniture_id ORDER BY (rr.status = 'Pending') DESC, rr.created_at DESC LIMIT 8");
$customerReviews = dashboard_rows($conn, "SELECT pr.order_id, pr.rating, pr.review_text, pr.created_at, u.name AS customer_name, f.name AS product_name FROM product_reviews pr LEFT JOIN users u ON u.id = pr.user_id LEFT JOIN furniture f ON f.furniture_id = pr.furniture_id WHERE pr.review_text IS NOT NULL AND pr.review_text <> '' ORDER BY pr.created_at DESC LIMIT 6");

$chartOrderLabels = ['Pending', 'Accepted', 'Denied', 'Other'];
$chartOrderValues = array_values($orderStatusCounts);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Overview | Furnara</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= app_url('assets/css/admin_sidebar.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/support_chat_widget.css') ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="<?= app_url('assets/css/admin_dashboard.css') ?>">
    <link rel="stylesheet" href="https://unpkg.com/maplibre-gl@5.6.2/dist/maplibre-gl.css">
</head>
<body>
    <?php include dirname(__DIR__, 2) . '/components/navigation/admin_sidebar.php'; ?>
    <main class="main-content"><div class="dashboard-wrap">
        <header class="topbar"><div><p class="eyebrow">Furnara / Admin</p><h1>Overview dashboard</h1><p class="welcome-copy">Welcome back. Review your store activity and performance.</p></div><div class="topbar-actions"><div class="date-chip"><i class="bi bi-calendar3"></i> <?= date('F j, Y') ?> <span aria-hidden="true"> / </span> Updated from current records</div><button class="add-product-button" type="button" id="open-add-product"><i class="bi bi-plus-lg"></i> Add product</button></div></header>
        <?php if ($productAddedNotice): ?><div class="dashboard-success-toast" role="status"><span class="dashboard-success-icon"><i class="bi bi-check-lg"></i></span><span><strong>Product added successfully</strong><small><?= htmlspecialchars($addedProductName !== '' ? $addedProductName . ' is now in your catalog.' : 'Your new item is now in the catalog.', ENT_QUOTES, 'UTF-8') ?></small></span></div><?php endif; ?>
        <?php if ($stockAlerts): ?><section class="inventory-alert-banner" role="alert" aria-labelledby="inventory-alert-title"><div class="inventory-alert-icon"><i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i></div><div class="inventory-alert-copy"><h2 id="inventory-alert-title">Inventory needs attention</h2><p><?= number_format($outOfStockCount) ?> out of stock · <?= number_format($lowStockCount) ?> low stock (fewer than 5 units)</p><ul><?php foreach ($stockAlerts as $stockAlert): ?><li><strong><?= htmlspecialchars((string)$stockAlert['name'], ENT_QUOTES, 'UTF-8') ?></strong> — <?= (int)$stockAlert['stock'] < 1 ? 'Out of stock' : 'Only ' . number_format((int)$stockAlert['stock']) . ' left' ?></li><?php endforeach; ?></ul></div><a href="<?= app_url('pages/admin/manage_products.php') ?>">Review inventory <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a></section><?php endif; ?>
        <section class="overview-grid" aria-label="Store overview">
            <article class="metric-card metric-purple"><div class="metric-top">Total orders <span class="metric-icon"><i class="bi bi-receipt"></i></span></div><p class="metric-value"><?= number_format($totalOrders) ?></p><p class="metric-note"><?= number_format($orderStatusCounts['Pending']) ?> awaiting review</p></article>
            <article class="metric-card metric-blue"><div class="metric-top">Accepted sales <span class="metric-icon"><i class="bi bi-cash-stack"></i></span></div><p class="metric-value"><?= dashboard_money($revenue) ?></p><p class="metric-note"><?= dashboard_money($todayRevenue) ?> today / <?= dashboard_money($monthRevenue) ?> this month</p></article>
            <article class="metric-card metric-green"><div class="metric-top">Customers <span class="metric-icon"><i class="bi bi-people"></i></span></div><p class="metric-value"><?= number_format($customerCount) ?></p><p class="metric-note">Registered non-admin accounts</p></article>
            <article class="metric-card metric-purple"><div class="metric-top">Products <span class="metric-icon"><i class="bi bi-box-seam"></i></span></div><p class="metric-value"><?= number_format($productCount) ?></p><p class="metric-note"><?= number_format($lowStockCount) ?> low stock / <?= number_format($outOfStockCount) ?> unavailable</p></article>
            <article class="metric-card metric-orange"><div class="metric-top">Delivery orders <span class="metric-icon"><i class="bi bi-truck"></i></span></div><p class="metric-value"><?= number_format($deliveryOrderCount) ?></p><p class="metric-note"><?= number_format($deliveryProductUnits) ?> product units in delivery orders</p></article>
            <article class="metric-card metric-blue"><div class="metric-top">Active discounts <span class="metric-icon"><i class="bi bi-tags"></i></span></div><p class="metric-value"><?= number_format($activeDiscountCount) ?></p><p class="metric-note"><?= $scheduledDiscountCount ?> scheduled / <?= $expiredDiscountCount ?> expired</p></article>

            <section class="panel store-location-panel"><div class="panel-heading"><div><h2>Store delivery origin</h2><p>Set the starting point used for customer delivery routes.</p></div><i class="bi bi-geo-alt panel-link" aria-hidden="true"></i></div><form id="store-location-form" data-location-picker><div class="account-location-search"><input class="form-control" type="search" data-location-search placeholder="Search store address" autocomplete="off"><button type="button" data-location-search-button>Find</button></div><div class="account-location-map" data-location-map data-lat="<?= htmlspecialchars((string)($storeLocation['latitude'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-lng="<?= htmlspecialchars((string)($storeLocation['longitude'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" aria-label="Set store delivery origin on map"></div><div class="store-location-fields"><label>Store address<input class="form-control" name="address" data-location-address required maxlength="500" value="<?= htmlspecialchars((string)($storeLocation['address'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label><label>Latitude<input class="form-control" name="latitude" data-location-latitude type="number" min="-90" max="90" step="any" required value="<?= htmlspecialchars((string)($storeLocation['latitude'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label><label>Longitude<input class="form-control" name="longitude" data-location-longitude type="number" min="-180" max="180" step="any" required value="<?= htmlspecialchars((string)($storeLocation['longitude'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label></div><div class="store-location-actions"><small data-location-message>Map © CARTO · Address search © OpenStreetMap contributors</small><button class="add-product-button" type="submit">Save store pin</button></div><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($storeLocationToken, ENT_QUOTES, 'UTF-8') ?>"><p class="store-location-feedback" id="store-location-feedback" role="status"></p></form></section>

            <section class="panel revenue-panel"><div class="panel-heading"><div><h2>Sales performance</h2><p>Accepted order value, grouped by month</p></div><span class="panel-link">Last 6 months</span></div><div class="chart-box"><canvas id="revenue-chart" aria-label="Monthly accepted sales chart"></canvas></div></section>
            <section class="panel status-panel"><div class="panel-heading"><div><h2>Order status</h2><p>Payment records from both order channels</p></div><a class="panel-link" href="<?= app_url('pages/admin/Orders_Display.php') ?>">View orders <i class="bi bi-arrow-up-right"></i></a></div><div class="status-content"><div class="status-chart"><canvas id="order-status-chart" aria-label="Order status chart"></canvas></div><div class="legend-list"><?php foreach ($chartOrderLabels as $i => $label): ?><div class="legend-row"><span class="legend-label"><i class="legend-dot" style="--dot:<?= ['#7253e8','#36b37e','#f16f75','#aab3c5'][$i] ?>"></i><?= htmlspecialchars($label) ?></span><strong><?= number_format($chartOrderValues[$i]) ?></strong></div><?php endforeach; ?></div></div></section>

            <section class="panel product-panel"><div class="panel-heading"><div><h2>Product sales performance</h2><p><?php if ($bestSellingProduct): ?>Best seller: <strong><?= htmlspecialchars($bestSellingProduct['name']) ?></strong> (<?= number_format($bestSellingProduct['units']) ?> units) · Slowest: <strong><?= htmlspecialchars($lowSellingProduct['name']) ?></strong> (<?= number_format($lowSellingProduct['units']) ?>)<?php else: ?>No products in the catalog yet.<?php endif; ?></p></div><div class="chart-switch" role="group" aria-label="Product chart view"><button type="button" class="is-active" data-product-view="all">All products</button><button type="button" data-product-view="history">6-month leaders</button></div></div><div class="product-performance-scroll" id="product-all-view"><canvas id="product-sales-chart" aria-label="Accepted sold units for every product"></canvas></div><div class="chart-box product-history-view" id="product-history-view" hidden><canvas id="product-history-chart" aria-label="Monthly unit sales for leading products"></canvas></div></section>
            <section class="panel delivery-panel"><div class="panel-heading"><div><h2>Delivery workflow</h2><p>Payment orders and live tracking status</p></div><a class="panel-link" href="<?= app_url('pages/orders/Delivery_tracking.php') ?>">Manage <i class="bi bi-arrow-up-right"></i></a></div><div class="delivery-highlights"><div><strong><?= number_format($deliveryOrderCount) ?></strong><span>Delivery orders</span></div><div><strong><?= number_format($deliveryProductUnits) ?></strong><span>Product units</span></div><div><strong><?= number_format($deliveryInTransit) ?></strong><span>In progress</span></div><div><strong><?= number_format($deliveredCount) ?></strong><span>Delivered</span></div></div><div class="chart-box delivery-chart-box"><canvas id="delivery-chart" aria-label="Delivery tracking status chart"></canvas></div></section>

            <section class="panel table-panel"><div class="panel-heading"><div><h2>Recent orders</h2><p>Latest in-store and delivery payment activity</p></div><a class="panel-link" href="<?= app_url('pages/admin/Orders_Display.php') ?>">Full report <i class="bi bi-arrow-up-right"></i></a></div><div class="table-responsive"><table class="data-table"><thead><tr><th>Product</th><th>Customer</th><th>Channel</th><th>Status</th><th>Amount</th><th>Date</th></tr></thead><tbody><?php if ($recentOrders): foreach ($recentOrders as $order): ?><tr><td class="stock-name" title="<?= htmlspecialchars((string)$order['item']) ?>"><?= htmlspecialchars((string)$order['item']) ?></td><td>#<?= (int)$order['user_id'] ?></td><td><?= htmlspecialchars($order['channel']) ?></td><td><span class="status-pill status-<?= htmlspecialchars(strtolower(str_replace(' ', '-', $order['status']))) ?>"><?= htmlspecialchars($order['status']) ?></span></td><td><?= dashboard_money($order['amount']) ?></td><td><?= htmlspecialchars(date('M j, Y', strtotime((string)$order['created_at']))) ?></td></tr><?php endforeach; else: ?><tr><td colspan="6" class="empty-state">No payment records yet.</td></tr><?php endif; ?></tbody></table></div></section>
            <section class="panel discount-panel"><div class="panel-heading"><div><h2>Discount report</h2><p>Upcoming and active product offers</p></div><a class="panel-link" href="<?= app_url('pages/admin/manage_discounts.php') ?>">Manage discounts <i class="bi bi-arrow-up-right"></i></a></div><div class="table-responsive"><table class="data-table"><thead><tr><th>Product</th><th>Offer</th><th>Period</th></tr></thead><tbody><?php if ($allDiscounts): foreach ($allDiscounts as $discount): ?><tr><td class="stock-name" title="<?= htmlspecialchars($discount['name']) ?>"><?= htmlspecialchars($discount['name']) ?></td><td><?= number_format((float)$discount['discount_percentage'], 0) ?>%</td><td><?= htmlspecialchars(date('M j', strtotime((string)$discount['discount_start']))) ?> - <?= htmlspecialchars(date('M j', strtotime((string)$discount['discount_end']))) ?></td></tr><?php endforeach; else: ?><tr><td colspan="3" class="empty-state">No discounts have been created.</td></tr><?php endif; ?></tbody></table></div><div class="panel-heading" style="margin:18px 0 8px"><div><h2>Inventory watch</h2><p>Products with the lowest stock</p></div></div><div class="table-responsive"><table class="data-table"><thead><tr><th>Product</th><th>Stock</th><th>Price</th></tr></thead><tbody><?php if ($topProducts): foreach ($topProducts as $item): ?><tr><td class="stock-name" title="<?= htmlspecialchars($item['name']) ?>"><?= htmlspecialchars($item['name']) ?></td><td class="<?= (int)$item['stock'] <= 5 ? 'stock-alert' : '' ?>"><?= number_format((int)$item['stock']) ?></td><td><?= dashboard_money($item['price']) ?></td></tr><?php endforeach; else: ?><tr><td colspan="3" class="empty-state">No inventory records found.</td></tr><?php endif; ?></tbody></table></div></section>
            <section class="panel feedback-panel"><div class="panel-heading"><div><h2>Customer care &amp; product feedback</h2><p>Refund requests and verified post-delivery reviews</p></div></div><div class="feedback-columns"><div><h3>Refund requests</h3><div class="table-responsive"><table class="data-table"><thead><tr><th>Customer / product</th><th>Reason</th><th>Status</th><th>Action</th></tr></thead><tbody><?php if ($refundRequests): foreach ($refundRequests as $request): ?><tr><td><?= htmlspecialchars((string)($request['customer_name'] ?? 'Customer')) ?><br><small><?= htmlspecialchars((string)($request['product_name'] ?? 'Removed product')) ?> · Order #<?= (int)$request['order_id'] ?></small></td><td class="feedback-copy"><?= htmlspecialchars((string)$request['reason']) ?></td><td><?= htmlspecialchars((string)$request['status']) ?></td><td><?php if (strtolower((string)$request['status']) === 'pending'): ?><form class="refund-admin-actions" method="post" action="<?= app_url('api/admin/update_refund_request.php') ?>"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($refundActionToken) ?>"><input type="hidden" name="request_id" value="<?= (int)$request['id'] ?>"><button class="refund-approve" name="status" value="Approved">Approve</button><button class="refund-decline" name="status" value="Declined">Decline</button></form><?php else: ?><small><?= htmlspecialchars(date('M j', strtotime((string)$request['created_at']))) ?></small><?php endif; ?></td></tr><?php endforeach; else: ?><tr><td colspan="4" class="empty-state">No customer refund requests.</td></tr><?php endif; ?></tbody></table></div></div><div><h3>Recent reviews</h3><div class="table-responsive"><table class="data-table"><thead><tr><th>Product / customer</th><th>Rating</th><th>Review</th></tr></thead><tbody><?php if ($customerReviews): foreach ($customerReviews as $review): ?><tr><td><?= htmlspecialchars((string)($review['product_name'] ?? 'Product')) ?><br><small><?= htmlspecialchars((string)($review['customer_name'] ?? 'Customer')) ?> · Order #<?= (int)$review['order_id'] ?></small></td><td><?= str_repeat('★', max(0, min(5, (int)$review['rating']))) ?></td><td class="feedback-copy"><?= htmlspecialchars((string)$review['review_text']) ?></td></tr><?php endforeach; else: ?><tr><td colspan="3" class="empty-state">No written product reviews yet.</td></tr><?php endif; ?></tbody></table></div></div></div></section>
        </section><div class="footer-note">Furnara administration | Dashboard metrics reflect saved store records.</div>
    </div></main>
    <?php include dirname(__DIR__, 2) . '/components/admin/add_product_modal.php'; ?>
    <script src="https://unpkg.com/maplibre-gl@5.6.2/dist/maplibre-gl.js"></script>
    <script src="<?= app_url('assets/js/location_maps.js') ?>"></script>
    <script>
        document.getElementById('store-location-form')?.addEventListener('submit', async (event) => {
            event.preventDefault();
            const form = event.currentTarget;
            const feedback = document.getElementById('store-location-feedback');
            try {
                const response = await fetch('<?= app_url('api/admin/save_store_location.php') ?>', { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' } });
                const result = await response.json();
                feedback.textContent = result.message || 'Store location could not be saved.';
                feedback.classList.toggle('is-error', !response.ok);
            } catch (_) { feedback.textContent = 'Store location could not be saved. Check your connection.'; feedback.classList.add('is-error'); }
        });
        (() => {
            const modal = document.getElementById('add-product-modal');
            const openButton = document.getElementById('open-add-product');
            const closeButtons = modal.querySelectorAll('[data-close-product-modal]');
            let previousFocus = null;
            const openModal = () => {
                previousFocus = document.activeElement;
                modal.hidden = false;
                document.body.classList.add('product-modal-open');
                requestAnimationFrame(() => modal.classList.add('is-visible'));
                modal.querySelector('input:not([type="hidden"])').focus();
            };
            const closeModal = () => {
                modal.classList.remove('is-visible');
                document.body.classList.remove('product-modal-open');
                window.setTimeout(() => { modal.hidden = true; }, 220);
                if (previousFocus) previousFocus.focus();
            };
            openButton.addEventListener('click', openModal);
            closeButtons.forEach((button) => button.addEventListener('click', closeModal));
            modal.addEventListener('click', (event) => { if (event.target === modal) closeModal(); });
            document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !modal.hidden) closeModal(); });
            if (<?= $openAddProductModal ? 'true' : 'false' ?>) {
                openModal();
                window.history.replaceState({}, '', window.location.pathname);
            } else if (<?= $productAddedNotice ? 'true' : 'false' ?>) {
                window.history.replaceState({}, '', window.location.pathname);
            }
        })();

        const chartLabels = <?= json_encode($revenueLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        const chartRevenue = <?= json_encode($revenueValues, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        const orderLabels = <?= json_encode($chartOrderLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        const orderValues = <?= json_encode($chartOrderValues, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        const deliveryLabels = <?= json_encode($deliveryLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        const deliveryValues = <?= json_encode($deliveryValues, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        const productLabels = <?= json_encode($productChartLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        const productValues = <?= json_encode($productChartValues, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        const productHistory = <?= json_encode($productHistoryDatasets, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        Chart.defaults.font.family = 'Inter, Segoe UI, Arial, sans-serif';
        Chart.defaults.color = '#8590a7';
        const revenueCanvas = document.getElementById('revenue-chart');
        const revenueContext = revenueCanvas.getContext('2d');
        const gradient = revenueContext.createLinearGradient(0, 0, 0, 260);
        gradient.addColorStop(0, 'rgba(113, 82, 232, .30)');
        gradient.addColorStop(1, 'rgba(113, 82, 232, 0)');
        new Chart(revenueCanvas, {type:'line',data:{labels:chartLabels,datasets:[{label:'Accepted sales',data:chartRevenue,borderColor:'#7355e8',backgroundColor:gradient,fill:true,tension:.38,borderWidth:3,pointRadius:3,pointBackgroundColor:'#fff',pointBorderWidth:2,pointBorderColor:'#7355e8'}]},options:{maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{callbacks:{label:context=>' \u20B1'+Number(context.raw).toLocaleString('en-PH',{minimumFractionDigits:2})}}},scales:{x:{grid:{display:false},border:{display:false}},y:{beginAtZero:true,grid:{color:'#edf0f6'},border:{display:false},ticks:{callback:value=>'\u20B1'+Number(value).toLocaleString('en-PH')}}}}});
        new Chart(document.getElementById('order-status-chart'),{type:'doughnut',data:{labels:orderLabels,datasets:[{data:orderValues,backgroundColor:['#7253e8','#36b37e','#f16f75','#aab3c5'],borderWidth:0,hoverOffset:5}]},options:{maintainAspectRatio:false,cutout:'72%',plugins:{legend:{display:false}}}});
        const productCanvas = document.getElementById('product-sales-chart');
        productCanvas.style.height = Math.max(120, productLabels.length * 20) + 'px';
        new Chart(productCanvas,{type:'bar',data:{labels:productLabels,datasets:[{label:'Units sold',data:productValues,backgroundColor:productValues.map((_,i)=>i===0?'#7355e8':'rgba(66,133,244,.68)'),borderRadius:5,maxBarThickness:15}]},options:{indexAxis:'y',maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{callbacks:{label:context=>' '+context.raw+' units sold'}}},scales:{x:{beginAtZero:true,ticks:{precision:0},grid:{color:'#edf0f6'},border:{display:false}},y:{grid:{display:false},border:{display:false},ticks:{autoSkip:false,font:{size:10}}}}}});
        let historyChart = null;
        document.querySelectorAll('[data-product-view]').forEach((button)=>button.addEventListener('click',()=>{
            const showHistory = button.dataset.productView === 'history';
            document.querySelectorAll('[data-product-view]').forEach((tab)=>tab.classList.toggle('is-active',tab===button));
            document.getElementById('product-all-view').hidden = showHistory;
            document.getElementById('product-history-view').hidden = !showHistory;
            if (showHistory && !historyChart) {
                const historyCanvas = document.getElementById('product-history-chart');
                historyChart = new Chart(historyCanvas,{type:'line',data:{labels:chartLabels,datasets:productHistory.map((item)=>({label:item.label,data:item.data,borderColor:item.color,backgroundColor:item.color+'22',fill:false,tension:.32,borderWidth:2,pointRadius:2}))},options:{maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{boxWidth:9,font:{size:10}}}},scales:{x:{grid:{display:false},border:{display:false}},y:{beginAtZero:true,ticks:{precision:0},grid:{color:'#edf0f6'},border:{display:false}}}}});
            } else if (showHistory) historyChart.resize();
        }));
        const deliveryColors = ['#7355e8','#4285f4','#42c6c4','#36b37e','#f3b44b','#f16f75'];
        new Chart(document.getElementById('delivery-chart'),{type:'bar',data:{labels:deliveryLabels,datasets:[{data:deliveryValues,backgroundColor:deliveryLabels.map((_,i)=>deliveryColors[i%deliveryColors.length]),borderRadius:7,maxBarThickness:38}]},options:{maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{grid:{display:false},border:{display:false}},y:{beginAtZero:true,ticks:{precision:0},grid:{color:'#edf0f6'},border:{display:false}}}}});
    </script>
</body>
</html>
