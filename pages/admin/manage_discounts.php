<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';

if (empty($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    header('Location: ' . app_url('pages/auth/login.php'));
    exit;
}
if (empty($_SESSION['discount_csrf_token'])) {
    $_SESSION['discount_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['discount_csrf_token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $flash = ['type' => 'danger', 'text' => 'The request could not be completed.'];
    if (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
        $flash['text'] = 'Your session expired. Refresh the page and try again.';
    } elseif (isset($_POST['save_discount'])) {
        $productId = filter_var($_POST['furniture_id'] ?? null, FILTER_VALIDATE_INT);
        $percentage = filter_var($_POST['discount_percentage'] ?? null, FILTER_VALIDATE_FLOAT);
        $startDate = trim((string)($_POST['start_date'] ?? ''));
        $endDate = trim((string)($_POST['end_date'] ?? ''));
        $startValid = DateTime::createFromFormat('!Y-m-d', $startDate);
        $endValid = DateTime::createFromFormat('!Y-m-d', $endDate);
        $datesValid = $startValid && $endValid && $startValid->format('Y-m-d') === $startDate && $endValid->format('Y-m-d') === $endDate;

        if (!$productId || $percentage === false || $percentage < 1 || $percentage > 99) {
            $flash['text'] = 'Choose a product and enter a discount from 1% to 99%.';
        } elseif (!$datesValid || $startDate < date('Y-m-d') || $startDate > $endDate) {
            $flash['text'] = 'Choose a start date today or later, and make sure the end date is on or after it.';
        } else {
            $productCheck = $conn->prepare('SELECT price FROM furniture WHERE furniture_id = ? LIMIT 1');
            if (!$productCheck) {
                error_log('Discount product lookup prepare failed: ' . $conn->error);
                $flash['text'] = 'The product could not be found.';
            } else {
                $productCheck->bind_param('i', $productId);
                $productCheck->execute();
                $product = $productCheck->get_result()->fetch_assoc();
                $productCheck->close();
                if (!$product) {
                    $flash['text'] = 'The selected product no longer exists.';
                } else {
                    $discountedPrice = round((float)$product['price'] * (1 - ((float)$percentage / 100)), 2);
                    $save = $conn->prepare('UPDATE furniture SET discount_percentage = ?, discounted_price = ?, discount_start = ?, discount_end = ? WHERE furniture_id = ?');
                    if ($save) {
                        $percentageValue = (float)$percentage;
                        $save->bind_param('ddssi', $percentageValue, $discountedPrice, $startDate, $endDate, $productId);
                        $flash = $save->execute()
                            ? ['type' => 'success', 'text' => 'Discount saved successfully.']
                            : ['type' => 'danger', 'text' => 'The discount could not be saved. Please try again.'];
                        $save->close();
                    } else {
                        error_log('Discount update prepare failed: ' . $conn->error);
                        $flash['text'] = 'The discount could not be saved. Please try again.';
                    }
                }
            }
        }
    } elseif (isset($_POST['remove_discount'])) {
        $productId = filter_var($_POST['furniture_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$productId) {
            $flash['text'] = 'The selected product is invalid.';
        } else {
            $remove = $conn->prepare('UPDATE furniture SET discount_percentage = NULL, discounted_price = NULL, discount_start = NULL, discount_end = NULL WHERE furniture_id = ?');
            if ($remove) {
                $remove->bind_param('i', $productId);
                $flash = $remove->execute()
                    ? ['type' => 'success', 'text' => 'Discount removed successfully.']
                    : ['type' => 'danger', 'text' => 'The discount could not be removed.'];
                $remove->close();
            } else {
                error_log('Discount removal prepare failed: ' . $conn->error);
                $flash['text'] = 'The discount could not be removed. Please try again.';
            }
        }
    }
    $_SESSION['discount_flash'] = $flash;
    header('Location: ' . app_url('pages/admin/manage_discounts.php'));
    exit;
}

$flash = $_SESSION['discount_flash'] ?? null;
unset($_SESSION['discount_flash']);
$products = [];
$productResult = $conn->query('SELECT furniture_id, name, image, price, discount_percentage, discounted_price, discount_start, discount_end FROM furniture ORDER BY name ASC');
if ($productResult) {
    while ($product = $productResult->fetch_assoc()) $products[] = $product;
} else {
    error_log('Discount products query failed: ' . $conn->error);
}

$today = date('Y-m-d');
$discountedProducts = [];
$activeCount = 0;
$scheduledCount = 0;
$expiredCount = 0;
$totalSavings = 0.0;
foreach ($products as $product) {
    if ($product['discount_percentage'] === null || (float)$product['discount_percentage'] <= 0) continue;
    $product['discount_status'] = $product['discount_start'] > $today ? 'Scheduled' : ($product['discount_end'] < $today ? 'Expired' : 'Active');
    if ($product['discount_status'] === 'Active') $activeCount++;
    elseif ($product['discount_status'] === 'Scheduled') $scheduledCount++;
    else $expiredCount++;
    $totalSavings += max(0, (float)$product['price'] - (float)$product['discounted_price']);
    $discountedProducts[] = $product;
}

function discount_h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function discount_money($value): string { return '&#8369;' . number_format((float)$value, 2); }
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Discounts | Furnara Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= app_url('assets/css/admin_style.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/admin_sidebar.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/admin_discounts.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/support_chat_widget.css') ?>">
</head>
<body class="admin-discounts-page">
<?php require dirname(__DIR__, 2) . '/components/navigation/admin_sidebar.php'; ?>
<main class="main-content admin-discounts-main">
    <header class="discount-heading"><div><p class="discount-eyebrow">PROMOTIONS / PRICING</p><h1><i class="bi bi-tags"></i> Manage discounts</h1><p>Schedule offers and review discounted products in one place.</p></div><button type="button" class="discount-add-button" data-bs-toggle="modal" data-bs-target="#discountModal"><i class="bi bi-plus-lg"></i> Add new discount</button></header>
    <?php if ($flash): ?><div class="discount-alert is-<?= discount_h($flash['type']) ?>" role="status"><i class="bi <?= $flash['type'] === 'success' ? 'bi-check-circle' : 'bi-info-circle' ?>"></i><?= discount_h($flash['text']) ?></div><?php endif; ?>
    <section class="discount-metrics" aria-label="Discount overview">
        <article><span><i class="bi bi-tags"></i> Discounted products</span><strong><?= count($discountedProducts) ?></strong><small>Offers configured in the catalog</small></article>
        <article class="is-active"><span><i class="bi bi-check-circle"></i> Active now</span><strong><?= $activeCount ?></strong><small>Currently visible to customers</small></article>
        <article class="is-scheduled"><span><i class="bi bi-calendar-event"></i> Scheduled</span><strong><?= $scheduledCount ?></strong><small>Offers starting later</small></article>
        <article class="is-expired"><span><i class="bi bi-clock-history"></i> Expired</span><strong><?= $expiredCount ?></strong><small>Offers ready to remove or renew</small></article>
        <article class="is-savings"><span><i class="bi bi-piggy-bank"></i> Combined price reduction</span><strong><?= discount_money($totalSavings) ?></strong><small>Sum of savings across discounted products</small></article>
    </section>

    <section class="discount-catalog-panel">
        <header class="discount-catalog-heading"><div><span class="discount-panel-icon"><i class="bi bi-ticket-perforated"></i></span><div><h2>Discounted products</h2><p>Each card shows the offer price and its scheduled period.</p></div></div><span class="discount-count"><?= count($discountedProducts) ?> offers</span></header>
        <?php if (!$discountedProducts): ?><div class="discount-empty"><i class="bi bi-tag"></i><strong>No product discounts yet</strong><span>Use “Add new discount” to schedule your first offer.</span></div><?php else: ?>
        <div class="discount-card-list">
            <?php foreach ($discountedProducts as $product):
                $image = trim((string)($product['image'] ?? ''));
                $imageUrl = $image === '' ? '' : app_url((str_starts_with($image, 'uploads/') || str_starts_with($image, 'pictures/') ? $image : 'uploads/' . ltrim($image, '/')));
                $saving = max(0, (float)$product['price'] - (float)$product['discounted_price']);
            ?>
            <article class="discount-product-card">
                <div class="discount-product-media"><?php if ($imageUrl): ?><img src="<?= discount_h($imageUrl) ?>" alt="<?= discount_h($product['name']) ?>"><?php else: ?><i class="bi bi-box-seam"></i><?php endif; ?><span class="discount-percent-badge"><?= number_format((float)$product['discount_percentage'], 0) ?>% OFF</span></div>
                <div class="discount-product-content"><div class="discount-product-top"><div><h3 title="<?= discount_h($product['name']) ?>"><?= discount_h($product['name']) ?></h3><span class="discount-product-id">Product #<?= (int)$product['furniture_id'] ?></span></div><span class="discount-state is-<?= strtolower(discount_h($product['discount_status'])) ?>"><?= discount_h($product['discount_status']) ?></span></div>
                    <div class="discount-price-line"><div><span>Offer price</span><strong><?= discount_money($product['discounted_price']) ?></strong></div><div class="discount-original"><span>Original price</span><strong><?= discount_money($product['price']) ?></strong></div><div class="discount-saved"><span>Save per item</span><strong><?= discount_money($saving) ?></strong></div></div>
                    <div class="discount-period"><i class="bi bi-calendar3"></i><span><?= discount_h(date('M j, Y', strtotime((string)$product['discount_start']))) ?> – <?= discount_h(date('M j, Y', strtotime((string)$product['discount_end']))) ?></span></div>
                    <form method="post" class="discount-remove-form" onsubmit="return confirm('Remove this product discount?')"><input type="hidden" name="csrf_token" value="<?= discount_h($csrfToken) ?>"><input type="hidden" name="furniture_id" value="<?= (int)$product['furniture_id'] ?>"><button type="submit" name="remove_discount" value="1"><i class="bi bi-trash3"></i> Remove discount</button></form>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>
</main>

<div class="modal fade discount-modal" id="discountModal" tabindex="-1" aria-labelledby="discountModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><form method="post" class="modal-content">
        <div class="modal-header"><div><p class="discount-eyebrow mb-1">PROMOTION SETUP</p><h2 class="modal-title" id="discountModalTitle">Add new discount</h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body"><input type="hidden" name="csrf_token" value="<?= discount_h($csrfToken) ?>">
            <label class="form-label" for="discount-product">Product</label><select class="form-select mb-3" id="discount-product" name="furniture_id" required><option value="">Choose a product...</option><?php foreach ($products as $product): ?><option value="<?= (int)$product['furniture_id'] ?>"><?= discount_h($product['name']) ?> · <?= discount_money($product['price']) ?><?= $product['discount_percentage'] !== null ? ' (current discount will be replaced)' : '' ?></option><?php endforeach; ?></select>
            <label class="form-label" for="discount-percentage">Discount percentage</label><div class="input-group mb-3"><input class="form-control" id="discount-percentage" name="discount_percentage" type="number" min="1" max="99" step="0.01" placeholder="e.g. 15" required><span class="input-group-text">%</span></div>
            <div class="row g-3"><div class="col-sm-6"><label class="form-label" for="discount-start">Start date</label><input class="form-control" id="discount-start" name="start_date" type="date" required></div><div class="col-sm-6"><label class="form-label" for="discount-end">End date</label><input class="form-control" id="discount-end" name="end_date" type="date" required></div></div>
            <p class="discount-modal-note"><i class="bi bi-info-circle"></i> The discounted price is calculated from the product’s regular price and shown to customers during the selected dates.</p>
        </div>
        <div class="modal-footer"><button type="button" class="discount-cancel-button" data-bs-dismiss="modal">Cancel</button><button type="submit" name="save_discount" value="1" class="discount-save-button"><i class="bi bi-check2"></i> Save discount</button></div>
    </form></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(() => {
    const start = document.getElementById('discount-start');
    const end = document.getElementById('discount-end');
    const today = new Date();
    const localToday = new Date(today.getTime() - today.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
    start.min = localToday;
    start.addEventListener('change', () => { end.min = start.value || localToday; });
})();
</script>
</body>
</html>
