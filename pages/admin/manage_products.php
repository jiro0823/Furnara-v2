<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';

if (empty($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    header('Location: ' . app_url('pages/auth/login.php'));
    exit;
}
if (empty($_SESSION['product_form_token'])) {
    $_SESSION['product_form_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['product_form_token'];
$manageError = '';

function manage_product_money($amount): string
{
    return '&#8369;' . number_format((float)$amount, 2);
}

function manage_product_image(array $file, bool $required = false): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE && !$required) return null;
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException('Choose a valid product image.');
    if ((int)$file['size'] > 8 * 1024 * 1024) throw new RuntimeException('The image must be 8 MB or smaller.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime])) throw new RuntimeException('Use a JPG, PNG, or WEBP image.');
    $name = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    $directory = APP_ROOT . '/uploads';
    if (!is_dir($directory) && !mkdir($directory, 0755, true)) throw new RuntimeException('The upload folder could not be prepared.');
    if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $name)) throw new RuntimeException('The product image could not be uploaded.');
    return $name;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrfToken, (string)($_POST['product_form_token'] ?? ''))) {
        $manageError = 'This form expired. Reload the page and try again.';
    } elseif (isset($_POST['add_product'])) {
        $form = [
            'name' => trim((string)($_POST['name'] ?? '')),
            'price' => trim((string)($_POST['price'] ?? '')),
            'description' => trim((string)($_POST['description'] ?? '')),
            'furniture_type' => strtolower(trim((string)($_POST['furniture_type'] ?? ''))),
            'color' => trim((string)($_POST['color'] ?? '')),
            'size' => trim((string)($_POST['size'] ?? '')),
            'material' => trim((string)($_POST['material'] ?? '')),
            'stock' => trim((string)($_POST['stock'] ?? '')),
        ];
        $allowedTypes = ['chair', 'table', 'sofa', 'bed', 'cabinet', 'desk'];
        $nameLength = function_exists('mb_strlen') ? mb_strlen($form['name']) : strlen($form['name']);
        $errors = [];
        if ($form['name'] === '' || $nameLength > 180) $errors[] = 'Enter a product name of 180 characters or fewer.';
        if (!is_numeric($form['price']) || (float)$form['price'] < 0) $errors[] = 'Enter a valid price.';
        if ($form['description'] === '') $errors[] = 'Add a product description.';
        if (!in_array($form['furniture_type'], $allowedTypes, true)) $errors[] = 'Choose a valid product type.';
        if ($form['color'] === '' || $form['size'] === '' || $form['material'] === '') $errors[] = 'Color, size, and material are required.';
        if ($form['stock'] === '' || filter_var($form['stock'], FILTER_VALIDATE_INT) === false || (int)$form['stock'] < 1) $errors[] = 'New products must have at least 1 unit in stock.';
        if (!$errors) {
            $imageName = null;
            try {
                $imageName = manage_product_image($_FILES['image'] ?? [], true);
                $statement = mysqli_prepare($conn, 'INSERT INTO furniture (name, price, description, furniture_type, color, size, material, stock, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                if (!$statement) throw new RuntimeException('The product could not be saved. Please try again.');
                $price = (float)$form['price'];
                $stock = (int)$form['stock'];
                mysqli_stmt_bind_param($statement, 'sdsssssis', $form['name'], $price, $form['description'], $form['furniture_type'], $form['color'], $form['size'], $form['material'], $stock, $imageName);
                if (!mysqli_stmt_execute($statement)) throw new RuntimeException('The product could not be saved. Please review the details.');
                mysqli_stmt_close($statement);
                $_SESSION['product_form_token'] = bin2hex(random_bytes(32));
                header('Location: ' . app_url('pages/admin/admin.php') . '?product_added=1&product=' . rawurlencode($form['name']));
                exit;
            } catch (Throwable $error) {
                if ($imageName) @unlink(APP_ROOT . '/uploads/' . $imageName);
                $_SESSION['product_form_errors'] = [$error->getMessage()];
                $_SESSION['product_form_values'] = $form;
                header('Location: ' . app_url('pages/admin/admin.php') . '?add_product=1');
                exit;
            }
        } else {
            $_SESSION['product_form_errors'] = $errors;
            $_SESSION['product_form_values'] = $form;
            header('Location: ' . app_url('pages/admin/admin.php') . '?add_product=1');
            exit;
        }
    } elseif (isset($_POST['update_product'])) {
        $id = filter_var($_POST['update_id'] ?? null, FILTER_VALIDATE_INT);
        $name = trim((string)($_POST['update_name'] ?? ''));
        $priceInput = trim((string)($_POST['update_price'] ?? ''));
        $price = is_numeric($priceInput) ? (float)$priceInput : -1;
        $description = trim((string)($_POST['update_description'] ?? ''));
        $type = strtolower(trim((string)($_POST['update_furniture_type'] ?? '')));
        $color = trim((string)($_POST['update_color'] ?? ''));
        $size = trim((string)($_POST['update_size'] ?? ''));
        $material = trim((string)($_POST['update_material'] ?? ''));
        $stock = filter_var($_POST['update_stock'] ?? null, FILTER_VALIDATE_INT);
        if (!$id || $name === '' || $price < 0 || $description === '' || !in_array($type, ['chair', 'table', 'sofa', 'bed', 'cabinet', 'desk'], true) || $color === '' || $size === '' || $material === '' || $stock === false || $stock < 0) {
            $manageError = 'Check the product details and try saving again.';
        } else {
            $newImage = null;
            try {
                $oldImageStatement = mysqli_prepare($conn, 'SELECT image FROM furniture WHERE furniture_id = ? LIMIT 1');
                if (!$oldImageStatement) throw new RuntimeException('The product could not be loaded.');
                mysqli_stmt_bind_param($oldImageStatement, 'i', $id);
                mysqli_stmt_execute($oldImageStatement);
                $oldImageResult = mysqli_stmt_get_result($oldImageStatement);
                $oldImage = mysqli_fetch_assoc($oldImageResult)['image'] ?? null;
                mysqli_stmt_close($oldImageStatement);
                if ($oldImage === null) throw new RuntimeException('Product not found.');
                $newImage = manage_product_image($_FILES['update_image'] ?? []);
                if ($newImage !== null) {
                    $statement = mysqli_prepare($conn, 'UPDATE furniture SET name=?, price=?, description=?, furniture_type=?, color=?, size=?, material=?, stock=?, image=? WHERE furniture_id=?');
                    if (!$statement) throw new RuntimeException('The product could not be updated. Please try again.');
                    mysqli_stmt_bind_param($statement, 'sdsssssisi', $name, $price, $description, $type, $color, $size, $material, $stock, $newImage, $id);
                } else {
                    $statement = mysqli_prepare($conn, 'UPDATE furniture SET name=?, price=?, description=?, furniture_type=?, color=?, size=?, material=?, stock=? WHERE furniture_id=?');
                    if (!$statement) throw new RuntimeException('The product could not be updated. Please try again.');
                    mysqli_stmt_bind_param($statement, 'sdsssssii', $name, $price, $description, $type, $color, $size, $material, $stock, $id);
                }
                if (!mysqli_stmt_execute($statement)) throw new RuntimeException('The product could not be updated. Please try again.');
                mysqli_stmt_close($statement);
                if ($newImage && $oldImage && is_file(APP_ROOT . '/uploads/' . $oldImage)) @unlink(APP_ROOT . '/uploads/' . $oldImage);
                header('Location: ' . app_url('pages/admin/manage_products.php') . '?updated=1');
                exit;
            } catch (Throwable $error) {
                if ($newImage) @unlink(APP_ROOT . '/uploads/' . $newImage);
                $manageError = $error->getMessage();
            }
        }
    } elseif (isset($_POST['delete_product'])) {
        $id = filter_var($_POST['delete_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id) {
            $manageError = 'Invalid product selected.';
        } else {
            $imageStatement = mysqli_prepare($conn, 'SELECT image FROM furniture WHERE furniture_id = ? LIMIT 1');
            mysqli_stmt_bind_param($imageStatement, 'i', $id);
            mysqli_stmt_execute($imageStatement);
            $imageResult = mysqli_stmt_get_result($imageStatement);
            $imageName = mysqli_fetch_assoc($imageResult)['image'] ?? null;
            mysqli_stmt_close($imageStatement);
            $deleteStatement = mysqli_prepare($conn, 'DELETE FROM furniture WHERE furniture_id = ?');
            mysqli_stmt_bind_param($deleteStatement, 'i', $id);
            if (!mysqli_stmt_execute($deleteStatement)) {
                $manageError = 'The product could not be deleted.';
            } else {
                if ($imageName && is_file(APP_ROOT . '/uploads/' . $imageName)) @unlink(APP_ROOT . '/uploads/' . $imageName);
                header('Location: ' . app_url('pages/admin/manage_products.php') . '?deleted=1');
                exit;
            }
            mysqli_stmt_close($deleteStatement);
        }
    }
}

$notice = '';
if (isset($_GET['updated'])) $notice = 'Product updated successfully.';
if (isset($_GET['deleted'])) $notice = 'Product deleted successfully.';
$products = [];
$productResult = mysqli_query($conn, 'SELECT furniture_id, name, price, description, furniture_type, color, size, material, stock, image FROM furniture ORDER BY furniture_id DESC');
if ($productResult) while ($row = mysqli_fetch_assoc($productResult)) $products[] = $row;

$productSales = [];
foreach ($products as $product) {
    $key = strtolower(trim((string)$product['name']));
    $productSales[$key] = ['name' => (string)$product['name'], 'units' => 0, 'history' => []];
}
$salesRows = [];
$salesResult = mysqli_query($conn, "SELECT furniture_name, created_at FROM instore_payment WHERE LOWER(status) = 'accepted' UNION ALL SELECT furniture_name, created_at FROM delivery_payment WHERE LOWER(status) = 'accepted'");
if ($salesResult) while ($saleRow = mysqli_fetch_assoc($salesResult)) $salesRows[] = $saleRow;
$historyMonths = [];
$historyMonthKeys = [];
for ($offset = 5; $offset >= 0; $offset--) {
    $month = (new DateTimeImmutable('first day of this month'))->modify('-' . $offset . ' months');
    $historyMonths[] = $month->format('M');
    $historyMonthKeys[] = $month->format('Y-m');
}
foreach ($salesRows as $saleRow) {
    $createdAt = strtotime((string)$saleRow['created_at']);
    $monthKey = $createdAt ? date('Y-m', $createdAt) : '';
    foreach (preg_split('/,\s*/', (string)$saleRow['furniture_name']) as $orderItem) {
        $orderItem = trim($orderItem);
        if ($orderItem === '') continue;
        if (preg_match('/^(.*?)\s+x(\d+)$/i', $orderItem, $match)) {
            $key = strtolower(trim($match[1]));
            $quantity = max(1, (int)$match[2]);
        } else {
            // Older payment records may store a product without a quantity suffix.
            $key = strtolower($orderItem);
            $quantity = 1;
        }
        if (!isset($productSales[$key])) continue;
        $productSales[$key]['units'] += $quantity;
        if ($monthKey !== '') $productSales[$key]['history'][$monthKey] = ($productSales[$key]['history'][$monthKey] ?? 0) + $quantity;
    }
}
$productSalesList = array_values($productSales);
usort($productSalesList, static fn($a, $b) => $b['units'] <=> $a['units'] ?: strcasecmp($a['name'], $b['name']));
$bestSeller = $productSalesList[0] ?? null;
$lowestSeller = $productSalesList ? $productSalesList[count($productSalesList) - 1] : null;
$leaderDatasets = [];
foreach (array_slice(array_values(array_filter($productSalesList, static fn($item) => $item['units'] > 0)), 0, 5) as $index => $leader) {
    $leaderDatasets[] = [
        'label' => $leader['name'],
        'data' => array_map(static fn($month) => (int)($leader['history'][$month] ?? 0), $historyMonthKeys),
        'color' => ['#7355e8', '#4285f4', '#42c6c4', '#f3b44b', '#f16f75'][$index],
    ];
}
$salesChartLabels = array_column($productSalesList, 'name');
$salesChartValues = array_column($productSalesList, 'units');
$stockByType = [];
$totalUnitsInStock = 0;
$lowStockProducts = 0;
$outOfStockProducts = 0;
foreach ($products as $product) {
    $type = ucfirst(trim((string)$product['furniture_type'])) ?: 'Other';
    $stock = (int)$product['stock'];
    $stockByType[$type] = ($stockByType[$type] ?? 0) + 1;
    $totalUnitsInStock += max(0, $stock);
    if ($stock <= 0) $outOfStockProducts++;
    elseif ($stock < 5) $lowStockProducts++;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Products | Furnara Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= app_url('assets/css/admin_sidebar.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/support_chat_widget.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/admin_dashboard.css') ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .inventory-page{max-width:1500px;margin:auto}.inventory-heading{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:22px}.inventory-heading h1{margin:0;font-size:1.8rem}.inventory-heading p{margin:6px 0 0;color:#7f8aa3}.inventory-card{overflow:hidden;border:1px solid #e7ebf3;border-radius:16px;background:#fff;box-shadow:0 5px 22px rgba(32,51,93,.035)}.inventory-table-wrap{overflow:auto}.inventory-table{width:100%;min-width:1080px;border-collapse:collapse;font-size:.82rem}.inventory-table th{padding:13px 12px;border-bottom:1px solid #e7ebf3;color:#7f8aa3;font-size:.68rem;letter-spacing:.06em;text-align:left;text-transform:uppercase}.inventory-table td{padding:12px;border-bottom:1px solid #f0f2f7;vertical-align:middle}.inventory-table tr:last-child td{border-bottom:0}.inventory-thumbnail{width:58px;height:50px;border-radius:8px;background:#f7f7f7;object-fit:contain}.inventory-description{max-width:230px;overflow:hidden;color:#75819a;text-overflow:ellipsis;white-space:nowrap}.inventory-actions{display:flex;gap:7px}.inventory-action{display:grid;width:34px;height:34px;place-items:center;border:1px solid #e7ebf3;border-radius:8px;background:white;color:#59657d;cursor:pointer}.inventory-action:hover{color:#7355e8;background:#f5f3ff}.inventory-action.delete:hover{color:#d34d54;background:#fff4f4}.inventory-alert{margin-bottom:16px;padding:12px 15px;border:1px solid #bfead3;border-radius:10px;background:#f1fff7;color:#176b43}.inventory-error{margin-bottom:16px;padding:12px 15px;border:1px solid #f3cbc8;border-radius:10px;background:#fff5f4;color:#9f3d37}.edit-product-modal{position:fixed;inset:0;z-index:1000;display:none;align-items:center;justify-content:center;padding:20px;background:rgba(17,25,42,.58);backdrop-filter:blur(5px)}.edit-product-modal.is-open{display:flex}.edit-product-dialog{width:min(820px,100%);max-height:calc(100vh - 40px);overflow:auto;border-radius:18px;background:white;box-shadow:0 28px 90px rgba(9,18,37,.28);animation:edit-modal-in .24s ease both}.edit-product-header{display:flex;justify-content:space-between;align-items:center;padding:20px 24px;border-bottom:1px solid #edf0f6}.edit-product-header h2{margin:0;font-size:1.2rem}.edit-product-body{padding:20px 24px}.edit-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.edit-grid label{display:grid;gap:6px;color:#34415b;font-size:.78rem;font-weight:700}.edit-grid input,.edit-grid select,.edit-grid textarea{width:100%;padding:10px 12px;border:1px solid #dce2ed;border-radius:9px;background:#fff;color:#18243b;font:inherit;font-size:.85rem}.edit-grid textarea{min-height:80px;resize:vertical}.edit-wide{grid-column:1/-1}.edit-modal-actions{display:flex;justify-content:flex-end;gap:9px;margin-top:18px}.edit-modal-actions button{min-height:40px;padding:0 15px;border:1px solid #dce2ed;border-radius:9px;background:white;color:#59657d;font-weight:700;cursor:pointer}.edit-modal-actions button[type=submit]{border:0;background:linear-gradient(110deg,#7051e5,#498bf3);color:#fff}@keyframes edit-modal-in{from{opacity:0;transform:translateY(12px) scale(.98)}to{opacity:1;transform:translateY(0) scale(1)}}@media(max-width:700px){.main-content{padding:18px 12px}.inventory-heading{align-items:flex-start;flex-direction:column}.edit-grid{grid-template-columns:1fr}.edit-wide{grid-column:auto}.edit-product-modal{padding:10px}.edit-product-header,.edit-product-body{padding:16px}}
            .inventory-overview-link{display:inline-flex;align-items:center;gap:8px;padding:9px 13px;border:1px solid #e1e6ef;border-radius:10px;background:#fff;color:#59657d;font-size:.82rem;text-decoration:none}.inventory-overview-link:hover{background:#f5f3ff;color:#7355e8}.manage-products-layout{display:block}.product-overview-grid{display:grid;grid-template-columns:minmax(0,1.45fr) minmax(290px,.75fr);align-items:stretch;gap:16px;margin-bottom:18px}.performance-panel,.stock-overview-panel{display:flex;min-width:0;flex-direction:column;padding:17px}.performance-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:13px}.performance-heading h2,.inventory-panel-heading h2{margin:0;font-size:1.05rem;font-weight:750}.performance-heading p:not(.eyebrow),.inventory-panel-heading p{margin:5px 0 0;color:#7f8aa3;font-size:.74rem;line-height:1.4}.performance-chart-switch{display:flex;flex:0 0 auto;gap:3px;padding:3px;border-radius:9px;background:#f1f3f9}.performance-chart-switch button{padding:6px 8px;border:0;border-radius:7px;background:transparent;color:#68748d;font-size:.66rem;white-space:nowrap;cursor:pointer}.performance-chart-switch button.is-active{background:#fff;color:#684bdc;box-shadow:0 1px 5px rgba(30,40,70,.1)}.product-summary-cards{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:7px;margin-bottom:12px}.product-summary-cards article{min-width:0;padding:9px;border:1px solid #edf0f6;border-radius:10px;background:#fafbfe}.product-summary-cards span,.product-summary-cards small{display:block;overflow:hidden;color:#7f8aa3;font-size:.64rem;text-overflow:ellipsis;white-space:nowrap}.product-summary-cards strong{display:block;overflow:hidden;margin:4px 0 2px;color:#26334c;font-size:.82rem;text-overflow:ellipsis;white-space:nowrap}.product-summary-cards article:first-child strong{font-size:1.12rem}.sales-all-scroll{max-height:285px;overflow:auto;overscroll-behavior:contain}.sales-all-scroll canvas{display:block;width:100%!important}.sales-history-chart{position:relative;height:255px}.sales-history-chart[hidden]{display:none}.stock-summary{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;margin:14px 0 8px}.stock-summary article{padding:10px;border:1px solid #edf0f6;border-radius:10px;background:#fafbfe}.stock-summary span{display:block;color:#7f8aa3;font-size:.68rem}.stock-summary strong{display:block;margin-top:4px;color:#26334c;font-size:1.1rem}.stock-summary .stock-warning{color:#d88724}.stock-summary .stock-empty{color:#d34d54}.stock-chart-wrap{position:relative;height:200px;margin-top:auto}.stock-chart-wrap canvas{max-height:200px}.stock-chart-empty{display:grid;height:100%;place-items:center;color:#7f8aa3;font-size:.8rem}.inventory-panel{display:flex;min-width:0;max-height:min(690px,calc(100vh - 190px));min-height:400px;flex-direction:column}.inventory-panel-heading{padding:15px 17px 12px;border-bottom:1px solid #edf0f6}.inventory-table-wrap{min-height:0;overflow:auto;flex:1 1 auto;overscroll-behavior:contain}.inventory-table thead{position:sticky;top:0;z-index:2;background:#fff}.inventory-table th{background:#fff}.inventory-table td{padding:10px}.inventory-table .inventory-description{max-width:190px}.inventory-actions form{margin:0}.inventory-table-wrap::-webkit-scrollbar,.sales-all-scroll::-webkit-scrollbar{width:8px;height:8px}.inventory-table-wrap::-webkit-scrollbar-thumb,.sales-all-scroll::-webkit-scrollbar-thumb{border:2px solid transparent;border-radius:10px;background:#cbd3e1;background-clip:padding-box}.inventory-table-wrap,.sales-all-scroll{scrollbar-color:#cbd3e1 transparent;scrollbar-width:thin}@media(max-width:1100px){.product-overview-grid{grid-template-columns:minmax(0,1.15fr) minmax(260px,.85fr)}.inventory-panel{max-height:620px;min-height:420px}.sales-all-scroll{max-height:250px}}@media(max-width:850px){.product-overview-grid{grid-template-columns:1fr}.stock-chart-wrap{height:220px}.stock-chart-wrap canvas{max-height:220px}}@media(max-width:700px){.performance-heading{flex-direction:column}.performance-chart-switch{align-self:flex-start}.product-summary-cards{gap:5px}.product-summary-cards article{padding:7px 6px}.inventory-panel{max-height:70vh;min-height:360px}.inventory-panel-heading{padding:13px}}</style>
</head>
<body>
    <?php include dirname(__DIR__, 2) . '/components/navigation/admin_sidebar.php'; ?>
    <main class="main-content"><div class="inventory-page">
        <header class="inventory-heading"><div><p class="eyebrow">Inventory / Catalog</p><h1>Manage products</h1><p>Review and update product details, stock, and images.</p></div><a class="inventory-overview-link" href="<?= app_url('pages/admin/admin.php') ?>"><i class="bi bi-arrow-left"></i> Back to overview</a></header>
        <?php if ($notice): ?><div class="inventory-alert" role="status"><?= htmlspecialchars($notice) ?></div><?php endif; ?>
        <?php if ($manageError): ?><div class="inventory-error" role="alert"><?= htmlspecialchars($manageError) ?></div><?php endif; ?>
        <div class="product-overview-grid">
        <section class="inventory-card performance-panel" aria-labelledby="product-performance-title">
            <div class="performance-heading"><div><p class="eyebrow">Sales insight</p><h2 id="product-performance-title">Product sales performance</h2><p>Compare accepted sales across every catalog item.</p></div><div class="performance-chart-switch" role="group" aria-label="Product sales graph"><button type="button" class="is-active" data-sales-view="all">All products</button><button type="button" data-sales-view="history">6-month leaders</button></div></div>
            <div class="product-summary-cards"><article><span>Total products</span><strong><?= number_format(count($products)) ?></strong></article><article><span>Best seller</span><strong><?= htmlspecialchars($bestSeller['name'] ?? 'No products') ?></strong><small><?= number_format((int)($bestSeller['units'] ?? 0)) ?> units sold</small></article><article><span>Lowest sales</span><strong><?= htmlspecialchars($lowestSeller['name'] ?? 'No products') ?></strong><small><?= number_format((int)($lowestSeller['units'] ?? 0)) ?> units sold</small></article></div>
            <div class="sales-all-scroll" id="sales-all-view"><canvas id="manage-product-sales-chart" aria-label="Accepted units sold for all catalog products"></canvas></div>
            <div class="sales-history-chart" id="sales-history-view" hidden><canvas id="manage-product-history-chart" aria-label="Monthly sales of leading products over the last six months"></canvas></div>
        </section>
        <section class="inventory-card stock-overview-panel" aria-labelledby="stock-overview-title"><header class="inventory-panel-heading"><div><p class="eyebrow">Catalog health</p><h2 id="stock-overview-title">Stock overview</h2><p>Available units and products that need attention.</p></div></header><div class="stock-summary"><article><span>Units in stock</span><strong><?= number_format($totalUnitsInStock) ?></strong></article><article><span>Product types</span><strong><?= number_format(count($stockByType)) ?></strong></article><article><span>Low stock (1-4)</span><strong class="stock-warning"><?= number_format($lowStockProducts) ?></strong></article><article><span>Out of stock</span><strong class="stock-empty"><?= number_format($outOfStockProducts) ?></strong></article></div><div class="stock-chart-wrap"><?php if ($stockByType): ?><canvas id="product-type-chart" aria-label="Product count by furniture type"></canvas><?php else: ?><div class="stock-chart-empty">No inventory to summarize yet.</div><?php endif; ?></div></section>
        </div>
        <section class="inventory-card inventory-panel" aria-labelledby="inventory-list-title"><header class="inventory-panel-heading"><div><h2 id="inventory-list-title">Product inventory</h2><p><?= number_format(count($products)) ?> products &middot; Scroll this list to review the catalog</p></div></header><div class="inventory-table-wrap"><table class="inventory-table"><thead><tr><th>ID</th><th>Image</th><th>Name and description</th><th>Type</th><th>Color</th><th>Size</th><th>Material</th><th>Price</th><th>Stock</th><th>Actions</th></tr></thead><tbody>
        <?php if ($products): foreach ($products as $product): $payload = json_encode($product, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>
            <tr><td><?= (int)$product['furniture_id'] ?></td><td><img class="inventory-thumbnail" src="<?= app_url('uploads/' . rawurlencode((string)$product['image'])) ?>" alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>"></td><td><strong><?= htmlspecialchars($product['name']) ?></strong><div class="inventory-description" title="<?= htmlspecialchars($product['description']) ?>"><?= htmlspecialchars($product['description']) ?></div></td><td><?= htmlspecialchars(ucfirst($product['furniture_type'])) ?></td><td><?= htmlspecialchars($product['color'] ?? '') ?></td><td><?= htmlspecialchars($product['size'] ?? '') ?></td><td><?= htmlspecialchars($product['material'] ?? '') ?></td><td><?= manage_product_money($product['price']) ?></td><td><?= number_format((int)$product['stock']) ?></td><td><div class="inventory-actions"><button class="inventory-action edit-product-button" type="button" data-product="<?= htmlspecialchars($payload, ENT_QUOTES, 'UTF-8') ?>" aria-label="Edit <?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>"><i class="bi bi-pencil"></i></button><form method="post" onsubmit="return confirm('Delete this product?')"><input type="hidden" name="product_form_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="delete_id" value="<?= (int)$product['furniture_id'] ?>"><button class="inventory-action delete" type="submit" name="delete_product" aria-label="Delete <?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>"><i class="bi bi-trash"></i></button></form></div></td></tr>
        <?php endforeach; else: ?><tr><td colspan="10" class="empty-state">No products have been added yet.</td></tr><?php endif; ?>
        </tbody></table></div></section>
    </div></main>

    <div class="edit-product-modal" id="edit-product-modal" aria-hidden="true"><section class="edit-product-dialog" role="dialog" aria-modal="true" aria-labelledby="edit-product-title"><header class="edit-product-header"><h2 id="edit-product-title">Edit product</h2><button class="inventory-action" type="button" data-close-edit aria-label="Close edit form"><i class="bi bi-x-lg"></i></button></header><div class="edit-product-body"><form method="post" enctype="multipart/form-data"><input type="hidden" name="product_form_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="update_id" id="edit-id"><div class="edit-grid"><label>Product name<input name="update_name" id="edit-name" required maxlength="180"></label><label>Type<select name="update_furniture_type" id="edit-type" required><?php foreach (['chair','table','sofa','bed','cabinet','desk'] as $type): ?><option value="<?= $type ?>"><?= ucfirst($type) ?></option><?php endforeach; ?></select></label><label>Price<input type="number" name="update_price" id="edit-price" min="0" step=".01" required></label><label>Stock<input type="number" name="update_stock" id="edit-stock" min="0" step="1" required></label><label>Color<input name="update_color" id="edit-color" required></label><label>Size<input name="update_size" id="edit-size" required></label><label>Material<input name="update_material" id="edit-material" required></label><label>Replace image (optional)<input type="file" name="update_image" accept="image/jpeg,image/png,image/webp"></label><label class="edit-wide">Description<textarea name="update_description" id="edit-description" required></textarea></label></div><div class="edit-modal-actions"><button type="button" data-close-edit>Cancel</button><button type="submit" name="update_product">Save changes</button></div></form></div></section></div>

    <script>
        (()=>{const modal=document.getElementById('edit-product-modal');const close=()=>{modal.classList.remove('is-open');modal.setAttribute('aria-hidden','true');document.body.classList.remove('product-modal-open')};document.querySelectorAll('.edit-product-button').forEach(button=>button.addEventListener('click',()=>{const product=JSON.parse(button.dataset.product);document.getElementById('edit-id').value=product.furniture_id;document.getElementById('edit-name').value=product.name;document.getElementById('edit-type').value=product.furniture_type;document.getElementById('edit-price').value=product.price;document.getElementById('edit-stock').value=product.stock;document.getElementById('edit-color').value=product.color||'';document.getElementById('edit-size').value=product.size||'';document.getElementById('edit-material').value=product.material||'';document.getElementById('edit-description').value=product.description;modal.classList.add('is-open');modal.setAttribute('aria-hidden','false');document.body.classList.add('product-modal-open')}));modal.querySelectorAll('[data-close-edit]').forEach(button=>button.addEventListener('click',close));modal.addEventListener('click',event=>{if(event.target===modal)close()});document.addEventListener('keydown',event=>{if(event.key==='Escape'){close();document.querySelector('[data-close-edit]')?.click()}})})();
    </script>
    <script>
        const manageSalesLabels = <?= json_encode($salesChartLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        const manageSalesValues = <?= json_encode($salesChartValues, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        const manageHistoryLabels = <?= json_encode($historyMonths, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        const manageLeaderDatasets = <?= json_encode($leaderDatasets, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        const productTypeLabels = <?= json_encode(array_keys($stockByType), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        const productTypeValues = <?= json_encode(array_values($stockByType), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        const typeChartCanvas = document.getElementById('product-type-chart');
        if(typeChartCanvas){new Chart(typeChartCanvas,{type:'doughnut',data:{labels:productTypeLabels,datasets:[{data:productTypeValues,backgroundColor:['#7355e8','#4285f4','#42c6c4','#f3b44b','#f16f75','#92a0b8'],borderColor:'#fff',borderWidth:3,hoverOffset:5}]},options:{maintainAspectRatio:false,cutout:'65%',plugins:{legend:{position:'bottom',labels:{boxWidth:9,padding:12,font:{size:10}}}}}})}
        const allSalesCanvas = document.getElementById('manage-product-sales-chart');
        allSalesCanvas.style.height = Math.max(130, manageSalesLabels.length * 23) + 'px';
        new Chart(allSalesCanvas,{type:'bar',data:{labels:manageSalesLabels,datasets:[{label:'Units sold',data:manageSalesValues,backgroundColor:manageSalesValues.map((_,index)=>index===0?'#7355e8':'rgba(66,133,244,.68)'),borderRadius:5,maxBarThickness:17}]},options:{indexAxis:'y',maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{callbacks:{label:context=>' '+context.raw+' units sold'}}},scales:{x:{beginAtZero:true,ticks:{precision:0},grid:{color:'#edf0f6'},border:{display:false}},y:{grid:{display:false},border:{display:false},ticks:{autoSkip:false,font:{size:10}}}}}});
        let manageHistoryChart=null;
        document.querySelectorAll('[data-sales-view]').forEach(button=>button.addEventListener('click',()=>{const history=button.dataset.salesView==='history';document.querySelectorAll('[data-sales-view]').forEach(tab=>tab.classList.toggle('is-active',tab===button));document.getElementById('sales-all-view').hidden=history;document.getElementById('sales-history-view').hidden=!history;if(history&&!manageHistoryChart){manageHistoryChart=new Chart(document.getElementById('manage-product-history-chart'),{type:'line',data:{labels:manageHistoryLabels,datasets:manageLeaderDatasets.map(item=>({label:item.label,data:item.data,borderColor:item.color,backgroundColor:item.color+'22',tension:.32,borderWidth:2,pointRadius:2}))},options:{maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{boxWidth:9,font:{size:10}}}},scales:{x:{grid:{display:false},border:{display:false}},y:{beginAtZero:true,ticks:{precision:0},grid:{color:'#edf0f6'},border:{display:false}}}}});}else if(history){manageHistoryChart.resize();}}));
    </script>
</body>
</html>
