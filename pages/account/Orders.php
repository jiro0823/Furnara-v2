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
$filter = strtolower((string)($_GET['status'] ?? 'all'));
$allowedFilters = ['all', 'processing', 'in-transit', 'delivered', 'cancelled'];
if (!in_array($filter, $allowedFilters, true)) $filter = 'all';

$sql = "SELECT o.order_id, o.public_order_number, o.delivery_fee, o.delivery_type, o.total_price, o.total_amount, o.order_date, o.status,
               o.furniture_name, o.delivery_name, o.delivery_address, o.pickup_date, o.pickup_time,
               (SELECT dp.delivery_date FROM delivery_payment dp WHERE dp.order_id = o.order_id AND dp.user_id = o.user_id ORDER BY dp.id DESC LIMIT 1) AS delivery_date,
               (SELECT dp.delivery_time FROM delivery_payment dp WHERE dp.order_id = o.order_id AND dp.user_id = o.user_id ORDER BY dp.id DESC LIMIT 1) AS delivery_time,
               (SELECT dp.delivery_address FROM delivery_payment dp WHERE dp.order_id = o.order_id AND dp.user_id = o.user_id ORDER BY dp.id DESC LIMIT 1) AS scheduled_address,
               (SELECT dp.status FROM delivery_payment dp WHERE dp.order_id = o.order_id AND dp.user_id = o.user_id ORDER BY dp.id DESC LIMIT 1) AS delivery_status,
               (SELECT ip.status FROM instore_payment ip WHERE ip.order_id = o.order_id AND ip.user_id = o.user_id ORDER BY ip.id DESC LIMIT 1) AS pickup_status,
               (SELECT dt.status FROM delivery_tracking dt WHERE dt.order_id = o.order_id AND dt.user_id = o.user_id ORDER BY dt.tracking_id DESC LIMIT 1) AS tracking_status,
               (SELECT dt.estimated_delivery FROM delivery_tracking dt WHERE dt.order_id = o.order_id AND dt.user_id = o.user_id ORDER BY dt.tracking_id DESC LIMIT 1) AS estimated_delivery
        FROM orders o
        WHERE o.user_id = ?
        ORDER BY o.order_date DESC, o.order_id DESC";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log('Order history query could not be prepared: ' . $conn->error);
    http_response_code(500);
    exit('Order history is unavailable. Please apply the migrations in database/migrations and refresh this page.');
}
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$orders = [];
$productLookup = $conn->prepare('SELECT furniture_id, name, description, image, price FROM furniture WHERE LOWER(name) = LOWER(?) LIMIT 1');

while ($row = $result->fetch_assoc()) {
    if (empty($row['public_order_number'])) {
        $newOrderNumber = strtoupper(bin2hex(random_bytes(10)));
        $referenceUpdate = $conn->prepare('UPDATE orders SET public_order_number = ? WHERE order_id = ? AND user_id = ? AND (public_order_number IS NULL OR public_order_number = \'\')');
        $referenceUpdate->bind_param('sii', $newOrderNumber, $row['order_id'], $userId);
        $referenceUpdate->execute();
        $referenceUpdate->close();
        $row['public_order_number'] = $newOrderNumber;
    }
    $row['display_status'] = $row['tracking_status'] ?: ($row['delivery_status'] ?: ($row['pickup_status'] ?: $row['status']));
    $row['is_delivered'] = in_array(strtolower((string)$row['display_status']), ['delivered', 'received', 'completed'], true);
    $row['is_cancelled'] = str_contains(strtolower((string)$row['display_status']), 'cancel');
    $row['products'] = [];
    $parts = preg_split('/\s*,\s*/', trim((string)$row['furniture_name']), -1, PREG_SPLIT_NO_EMPTY);
    foreach ($parts as $part) {
        $productName = trim($part);
        $quantity = 1;
        if (preg_match('/^(.*?)\s+x(\d+)$/i', $part, $matches)) {
            $productName = trim($matches[1]);
            $quantity = max(1, (int)$matches[2]);
        }
        $productLookup->bind_param('s', $productName);
        $productLookup->execute();
        $product = $productLookup->get_result()->fetch_assoc();
        $product = $product ?: ['furniture_id' => 0, 'name' => $productName, 'description' => '', 'image' => '', 'price' => 0];
        $imageFile = (string)($product['image'] ?? '');
        $product['image_url'] = $imageFile !== '' && is_file(APP_ROOT . '/uploads/' . $imageFile)
            ? app_url('uploads/' . rawurlencode($imageFile))
            : app_url('pictures/bg-chair.png');
        $product['quantity'] = $quantity;
        $row['products'][] = $product;
    }
    $orders[] = $row;
}
$productLookup->close();
$stmt->close();

$reviews = [];
$reviewResult = $conn->prepare('SELECT order_id, furniture_id, rating, review_text FROM product_reviews WHERE user_id = ?');
if ($reviewResult && $reviewResult->execute()) {
    $reviewRows = $reviewResult->get_result();
    while ($review = $reviewRows->fetch_assoc()) $reviews[(int)$review['order_id'] . ':' . (int)$review['furniture_id']] = $review;
    $reviewResult->close();
}
$refunds = [];
$refundResult = $conn->prepare('SELECT order_id, furniture_id, status FROM refund_requests WHERE user_id = ?');
if ($refundResult && $refundResult->execute()) {
    $refundRows = $refundResult->get_result();
    while ($refund = $refundRows->fetch_assoc()) $refunds[(int)$refund['order_id'] . ':' . (int)$refund['furniture_id']] = $refund;
    $refundResult->close();
}

$visibleOrders = array_values(array_filter($orders, static function (array $order) use ($filter): bool {
    $status = strtolower((string)$order['display_status']);
    return match ($filter) {
        'processing' => !$order['is_delivered'] && !$order['is_cancelled'] && !str_contains($status, 'transit'),
        'in-transit' => str_contains($status, 'transit') || str_contains($status, 'ship'),
        'delivered' => $order['is_delivered'],
        'cancelled' => $order['is_cancelled'],
        default => true,
    };
}));
function account_h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function account_money($value): string { return '₱' . number_format((float)$value, 2); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Orders &amp; Delivery | Furnara</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= app_url('assets/css/user_sidebar.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/account_pages.css') ?>">
    <?php if ($isDrawerView): ?><link rel="stylesheet" href="<?= app_url('assets/css/account_drawer_embedded.css') ?>"><?php endif; ?>
    <link rel="stylesheet" href="<?= app_url('assets/css/support_chat_widget.css') ?>">
</head>
<body class="account-page<?= $isDrawerView ? ' account-embedded' : '' ?>">
<div class="account-shell">
    <?php if (!$isDrawerView) require dirname(__DIR__, 2) . '/components/navigation/user_sidebar.php'; ?>
    <main class="account-main">
        <div class="account-content">
            <p class="account-eyebrow">Your purchase history</p>
            <h1 class="account-page-title">Orders &amp; delivery</h1>
            <p class="account-page-subtitle">Follow each order from confirmation to delivery, and share feedback when it arrives.</p>
            <nav class="account-filter-row" aria-label="Filter orders">
                <?php foreach (['all' => 'All orders', 'processing' => 'Processing', 'in-transit' => 'In transit', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'] as $key => $label): ?>
                    <a class="account-filter-chip <?= $filter === $key ? 'is-active' : '' ?>" href="<?= app_url('pages/account/Orders.php') ?>?status=<?= urlencode($key) ?><?= $isDrawerView ? '&amp;drawer=1' : '' ?>"><?= account_h($label) ?></a>
                <?php endforeach; ?>
            </nav>

            <?php if (!$visibleOrders): ?>
                <section class="account-card order-empty"><i class="bi bi-box-seam"></i><h2 class="h5">No orders here yet</h2><p class="mb-0">Your orders and delivery updates will appear here.</p></section>
            <?php else: ?>
                <div class="order-history-list">
                <?php foreach ($visibleOrders as $order): ?>
                    <?php
                        $orderId = (int)$order['order_id'];
                        $type = strtolower((string)$order['delivery_type']) === 'in-store' ? 'In-store pickup' : 'Delivery';
                        $savedTotal = (float)($order['total_amount'] ?: $order['total_price']);
                        $savedDeliveryFee = (float)($order['delivery_fee'] ?? 0);
                        $displayTotal = $savedTotal + ($type === 'Delivery' && $savedDeliveryFee <= 0 ? 100 : 0);
                        $expectedDate = $order['delivery_date'] ?: $order['pickup_date'];
                        if (!$expectedDate && !empty($order['estimated_delivery'])) $expectedDate = date('Y-m-d', strtotime((string)$order['estimated_delivery']));
                        $expectedTime = $order['delivery_time'] ?: $order['pickup_time'];
                        $expectedLabel = $expectedDate ? date('M j, Y', strtotime((string)$expectedDate)) : 'Date to be confirmed';
                        if ($expectedTime) $expectedLabel .= ' · ' . date('g:i A', strtotime((string)$expectedTime));
                    ?>
                    <article class="account-card order-history-card" data-order-href="<?= app_url('pages/account/order_detail.php?order=' . rawurlencode((string)$order['public_order_number']) . ($isDrawerView ? '&drawer=1' : '')) ?>" tabindex="0" role="link" aria-label="Open order <?= account_h($order['public_order_number']) ?> details">
                        <header class="order-history-head">
                            <div>
                                <div class="order-history-meta"><strong><?= account_h($type) ?> · Order <?= account_h($order['public_order_number']) ?></strong><span>Ordered <?= !empty($order['order_date']) ? date('M j, Y · g:i A', strtotime((string)$order['order_date'])) : '—' ?></span></div>
                                <div class="order-delivery-note"><i class="bi bi-calendar2-check"></i><span><strong>Expected arrival</strong><br><?= account_h($expectedLabel) ?><?= !empty($order['scheduled_address']) || !empty($order['delivery_address']) ? ' · ' . account_h($order['scheduled_address'] ?: $order['delivery_address']) : '' ?></span></div>
                            </div>
                            <span class="order-status-pill"><?= account_h($order['display_status'] ?: 'Processing') ?></span>
                        </header>

                        <div class="order-product-list">
                            <?php foreach ($order['products'] as $product): ?>
                                <?php $productId = (int)$product['furniture_id']; $reviewKey = $orderId . ':' . $productId; $savedReview = $reviews[$reviewKey] ?? null; $savedRefund = $refunds[$reviewKey] ?? null; ?>
                                <div class="order-product-row">
                                    <img class="order-product-image" src="<?= account_h($product['image_url']) ?>" alt="<?= account_h($product['name']) ?>">
                                    <div>
                                        <h2 class="order-product-name"><?= account_h($product['name']) ?></h2>
                                        <p class="order-product-description"><?= account_h($product['description'] ?: 'A thoughtful piece from your Furnara order.') ?></p>
                                        <p class="order-product-quantity">Quantity: <?= (int)$product['quantity'] ?></p>
                                    </div>
                                    <div class="order-product-price"><?= account_money((float)$product['price'] * (int)$product['quantity']) ?></div>
                                </div>
                                <?php if ($order['is_delivered'] && $productId > 0): ?>
                                    <section class="quick-review" aria-label="Quick review for <?= account_h($product['name']) ?>">
                                        <span class="quick-review-label"><?= $savedReview ? 'Your rating' : 'Quick review' ?></span>
                                        <div class="quick-review-stars" data-order-id="<?= $orderId ?>" data-product-id="<?= $productId ?>" data-csrf="<?= account_h($csrfToken) ?>" data-rating="<?= (int)($savedReview['rating'] ?? 0) ?>" role="group" aria-label="Rate this product">
                                            <?php for ($star = 1; $star <= 5; $star++): ?><button type="button" class="<?= $star <= (int)($savedReview['rating'] ?? 0) ? 'is-selected' : '' ?>" data-star="<?= $star ?>" aria-label="<?= $star ?> stars">★</button><?php endfor; ?>
                                        </div>
                                        <div class="order-actions">
                                            <?php if (!$savedRefund): ?><button type="button" class="order-action-button" data-feedback="refund" data-order-id="<?= $orderId ?>" data-product-id="<?= $productId ?>" data-product-name="<?= account_h($product['name']) ?>">Request refund</button><?php else: ?><span class="small text-secondary">Refund <?= account_h($savedRefund['status']) ?></span><?php endif; ?>
                                            <button type="button" class="order-action-button is-review" data-feedback="review" data-order-id="<?= $orderId ?>" data-product-id="<?= $productId ?>" data-product-name="<?= account_h($product['name']) ?>" data-rating="<?= (int)($savedReview['rating'] ?? 0) ?>"><?= $savedReview ? 'Edit review' : 'Write review' ?></button>
                                        </div>
                                    </section>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>

                        <footer class="order-history-footer">
                            <div><div class="order-total">Order total<strong><?= account_money($displayTotal) ?></strong></div></div>
                            <div class="order-actions">
                                <?php if (!$order['is_delivered'] && !$order['is_cancelled']): ?>
                                    <form method="post" action="<?= app_url('pages/orders/cancel_order.php') ?>" onsubmit="return confirm('Cancel this order?')"><input type="hidden" name="csrf_token" value="<?= account_h($csrfToken) ?>"><input type="hidden" name="order_number" value="<?= account_h($order['public_order_number']) ?>"><input type="hidden" name="drawer" value="<?= $isDrawerView ? '1' : '0' ?>"><button class="order-action-button" type="submit">Cancel order</button></form>
                                <?php endif; ?>
                                <a class="order-action-button" href="<?= app_url('pages/public/furniture.php') ?>">Continue shopping</a>
                            </div>
                        </footer>
                    </article>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<div class="modal fade account-modal" id="orderFeedbackModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><form id="orderFeedbackForm" class="modal-content">
        <div class="modal-header"><div><p class="account-eyebrow mb-1" id="feedback-eyebrow">Your feedback</p><h2 class="modal-title fs-5" id="feedback-title">Write a review</h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body">
            <p class="text-secondary" id="feedback-product-name"></p>
            <input type="hidden" name="csrf_token" value="<?= account_h($csrfToken) ?>"><input type="hidden" name="order_id"><input type="hidden" name="furniture_id"><input type="hidden" name="rating" value="5"><input type="hidden" name="action">
            <div class="mb-3" id="feedback-rating-row"><label class="form-label fw-semibold">Your rating</label><div class="quick-review-stars" id="modal-rating-stars"><?php for ($star=1;$star<=5;$star++): ?><button type="button" data-modal-star="<?= $star ?>" class="<?= $star === 5 ? 'is-selected' : '' ?>" aria-label="<?= $star ?> stars">★</button><?php endfor; ?></div></div>
            <div><label class="form-label fw-semibold" id="feedback-text-label" for="feedback-text">Your review</label><textarea class="form-control" id="feedback-text" name="text" rows="4" maxlength="1500" placeholder="Share what you liked or let us know about an issue."></textarea></div>
        </div>
        <div class="modal-footer"><button type="button" class="order-action-button" data-bs-dismiss="modal">Not now</button><button class="btn account-primary-button" type="submit">Submit</button></div>
    </form></div>
</div>
<div class="toast-container position-fixed bottom-0 end-0 p-3"><div class="toast" id="accountToast" role="status"><div class="toast-body"></div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(() => {
    const endpoint = <?= json_encode(app_url('api/account/order_feedback.php')) ?>;
    const modalElement = document.getElementById('orderFeedbackModal');
    const modal = new bootstrap.Modal(modalElement);
    const form = document.getElementById('orderFeedbackForm');
    const toast = new bootstrap.Toast(document.getElementById('accountToast'));
    const toastBody = document.querySelector('#accountToast .toast-body');
    let rating = 5;
    const setStars = (container, selected, attr) => container.querySelectorAll('button').forEach(button => button.classList.toggle('is-selected', Number(button.getAttribute(attr)) <= selected));

    document.querySelectorAll('[data-order-href]').forEach(card => {
        const open = () => window.location.href = card.dataset.orderHref;
        card.addEventListener('click', event => {
            if (!event.target.closest('a,button,form,input,select,textarea')) open();
        });
        card.addEventListener('keydown', event => {
            if ((event.key === 'Enter' || event.key === ' ') && !event.target.closest('button,a,input,select,textarea')) {
                event.preventDefault();
                open();
            }
        });
    });

    document.querySelectorAll('[data-feedback]').forEach(button => button.addEventListener('click', () => {
        const isReview = button.dataset.feedback === 'review';
        form.reset();
        form.querySelector('[name="action"]').value = isReview ? 'review' : 'refund';
        form.elements.order_id.value = button.dataset.orderId;
        form.elements.furniture_id.value = button.dataset.productId;
        document.getElementById('feedback-product-name').textContent = button.dataset.productName;
        document.getElementById('feedback-title').textContent = isReview ? 'Write a review' : 'Request a refund';
        document.getElementById('feedback-eyebrow').textContent = isReview ? 'Product review' : 'Customer care';
        document.getElementById('feedback-text-label').textContent = isReview ? 'Your review' : 'Reason for refund request';
        document.getElementById('feedback-text').placeholder = isReview ? 'Share what you liked or let us know about an issue.' : 'Tell us briefly what went wrong with this product.';
        document.getElementById('feedback-rating-row').hidden = !isReview;
        rating = Number(button.dataset.rating || 5);
        form.elements.rating.value = String(rating);
        setStars(document.getElementById('modal-rating-stars'), rating, 'data-modal-star');
        modal.show();
    }));
    document.querySelectorAll('[data-star]').forEach(button => button.addEventListener('click', async () => {
        const group = button.closest('.quick-review-stars');
        const selected = Number(button.dataset.star);
        group.dataset.rating = String(selected);
        setStars(group, selected, 'data-star');
        const data = new URLSearchParams({action:'quick_rating', order_id:group.dataset.orderId, furniture_id:group.dataset.productId, rating:String(selected), csrf_token:group.dataset.csrf});
        try {
            const response = await fetch(endpoint, {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:data,credentials:'same-origin'});
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'Could not save rating.');
            toastBody.textContent = 'Your rating has been saved.';
        } catch (error) {
            toastBody.textContent = error.message;
        }
        toast.show();
    }));
    document.querySelectorAll('[data-modal-star]').forEach(button => button.addEventListener('click', () => {
        rating = Number(button.dataset.modalStar);
        form.elements.rating.value = String(rating);
        setStars(document.getElementById('modal-rating-stars'), rating, 'data-modal-star');
    }));
    form.addEventListener('submit', async event => {
        event.preventDefault();
        const response = await fetch(endpoint, {method:'POST',body:new FormData(form),credentials:'same-origin'});
        const result = await response.json();
        if (!response.ok || !result.success) {
            toastBody.textContent = result.message || 'Could not submit feedback.';
            toast.show();
            return;
        }
        modal.hide();
        toastBody.textContent = result.message;
        toast.show();
        window.setTimeout(() => window.location.reload(), 700);
    });
})();
</script>
</body>
</html>
