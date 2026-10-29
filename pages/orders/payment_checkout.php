<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';
if (empty($_SESSION['user_id'])) {
    header('Location: ' . app_url('pages/auth/login.php'));
    exit;
}
$draft = $_SESSION['checkout_draft'] ?? null;
if (!is_array($draft) || empty($draft['token']) || time() - (int)($draft['created_at'] ?? 0) > 1800) {
    unset($_SESSION['checkout_draft']);
    header('Location: ' . app_url('pages/public/furniture.php'));
    exit;
}
$userId = (int)$_SESSION['user_id'];
$items = [];
$statement = $conn->prepare("SELECT furniture_id, name, price, image, quantity FROM cart_items WHERE user_id = ? AND status = 'active' AND quantity > 0 ORDER BY id");
if ($statement) {
    $statement->bind_param('i', $userId);
    $statement->execute();
    $result = $statement->get_result();
    while ($row = $result->fetch_assoc()) $items[] = $row;
    $statement->close();
}
if (!$items) {
    header('Location: ' . app_url('pages/public/furniture.php'));
    exit;
}
$subtotal = 0.0;
foreach ($items as $item) $subtotal += (float)$item['price'] * (int)$item['quantity'];
$subtotal = round($subtotal, 2);
$deliveryFee = $draft['order_type'] === 'delivery' ? 100.0 : 0.0;
$total = round($subtotal + $deliveryFee, 2);
$csrf = $_SESSION['cart_csrf_token'] ?? '';
$orderLabel = $draft['order_type'] === 'delivery' ? 'Home delivery' : 'In-store pickup';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checkout | Furnara</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= app_url('assets/css/user_home.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/components/site_components.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/cart_drawer.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/account_drawer.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/support_chat_widget.css') ?>">
    <style>
        body{background:#f7f6f2;color:#252522}.checkout-shell{max-width:1120px;margin:54px auto 90px;padding:0 22px}.checkout-heading{margin-bottom:28px}.checkout-heading p,.checkout-eyebrow{color:#90754b;letter-spacing:.16em;text-transform:uppercase;font-size:.76rem;font-weight:700}.checkout-layout{display:grid;grid-template-columns:minmax(0,1.45fr) minmax(300px,.8fr);gap:24px}.checkout-panel{background:#fff;border:1px solid #e8e5df;border-radius:22px;padding:26px;box-shadow:0 14px 38px #29251b0a}.checkout-method{display:flex;align-items:flex-start;gap:14px;padding:17px;border:1px solid #e7e4dd;border-radius:15px;margin:13px 0;cursor:pointer;transition:.2s}.checkout-method-selected{border-color:#292a26;background:#f7f6f2;box-shadow:0 0 0 2px #292a2610}.checkout-method input{accent-color:#272923;margin-top:4px}.checkout-method i{font-size:1.3rem;color:#937950}.checkout-method strong{display:block}.checkout-muted{color:#77766f;font-size:.9rem}.checkout-item{display:grid;grid-template-columns:68px 1fr auto;align-items:center;gap:14px;padding:14px 0;border-bottom:1px solid #eee}.checkout-item img{width:68px;height:68px;object-fit:contain;background:#f7f6f2;border-radius:12px}.checkout-item-price{white-space:nowrap;font-weight:650}.checkout-totals{display:grid;gap:12px;margin-top:20px}.checkout-total{border-top:1px solid #e9e6df;padding-top:16px;font-size:1.13rem;font-weight:750}.checkout-submit{width:100%;border:0;border-radius:999px;background:#252723;color:#fff;font-weight:700;padding:15px 18px;margin-top:18px;transition:transform .2s,background .2s}.checkout-submit:hover{background:#3c4038;transform:translateY(-1px)}.checkout-submit:disabled{opacity:.65;cursor:wait}.checkout-back{color:#5e5b55}.checkout-alert{border-radius:12px}.checkout-info{background:#f3f1eb;border-radius:13px;padding:15px;margin:16px 0;color:#5c5b55}.checkout-status{min-height:0;margin-top:15px}.checkout-status:empty{display:none}@media(max-width:800px){.checkout-layout{grid-template-columns:1fr}.checkout-shell{margin-top:30px;padding:0 15px}.checkout-panel{padding:20px}}
    </style>
</head>
<body>
    <?php require dirname(__DIR__, 2) . '/components/navigation/user_home_navbar.php'; ?>
    <main class="checkout-shell" data-csrf="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
        <header class="checkout-heading">
            <a class="checkout-back" href="<?= app_url('pages/public/furniture.php') ?>"><i class="bi bi-arrow-left"></i> Back to shopping</a>
            <p class="checkout-eyebrow mt-4 mb-2">Secure checkout</p>
            <h1><?= htmlspecialchars($orderLabel) ?></h1>
            <p class="checkout-muted">Review your order and choose how you would like to pay.</p>
        </header>
        <div class="checkout-layout">
            <section class="checkout-panel">
                <h2 class="h4 mb-3">Payment method</h2>
                <form id="payment-choice-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="payment_method" value="<?= $draft['order_type'] === 'delivery' ? 'cash_on_delivery' : 'pay_at_store' ?>">
                    <div class="checkout-method checkout-method-selected" aria-label="Selected payment method">
                        <i class="bi <?= $draft['order_type'] === 'delivery' ? 'bi-truck' : 'bi-shop' ?>"></i>
                        <span><strong><?= $draft['order_type'] === 'delivery' ? 'Cash on delivery' : 'Pay at the store' ?></strong><span class="checkout-muted"><?= $draft['order_type'] === 'delivery' ? 'Pay when your furniture arrives. Your order and payment status will be saved to your account.' : 'Pay when you collect your pickup order. Your order and payment status will be saved to your account.' ?></span></span>
                    </div>
                    <?php if ($draft['order_type'] === 'delivery'): ?>
                        <div class="checkout-info"><strong><?= htmlspecialchars($draft['delivery_name']) ?></strong><br><span class="checkout-muted">Delivery address uses the map pin saved in your profile.<br>Requested: <?= htmlspecialchars($draft['delivery_date']) ?> at <?= htmlspecialchars($draft['delivery_time']) ?></span></div>
                    <?php else: ?>
                        <div class="checkout-info"><strong>Pickup schedule</strong><br><span class="checkout-muted"><?= htmlspecialchars($draft['pickup_date']) ?> at <?= htmlspecialchars($draft['pickup_time']) ?></span></div>
                    <?php endif; ?>
                    <button class="checkout-submit" id="place-order-button" type="submit">Place order</button>
                    <div class="checkout-status" id="checkout-status" role="status" aria-live="polite"></div>
                </form>
            </section>
            <aside class="checkout-panel">
                <h2 class="h4">Your order</h2>
                <?php foreach ($items as $item): ?>
                    <div class="checkout-item">
                        <?php $imageUrl = preg_match('~^https?://~i', (string)$item['image']) ? (string)$item['image'] : app_url(ltrim((string)$item['image'], '/')); ?>
                        <img src="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>">
                        <div><strong><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></strong><div class="checkout-muted">Qty <?= (int)$item['quantity'] ?> &middot; &#8369;<?= number_format((float)$item['price'], 2) ?> each</div></div>
                        <div class="checkout-item-price">&#8369;<?= number_format((float)$item['price'] * (int)$item['quantity'], 2) ?></div>
                    </div>
                <?php endforeach; ?>
                <div class="checkout-totals">
                    <div class="d-flex justify-content-between"><span class="checkout-muted">Items subtotal</span><strong>&#8369;<?= number_format($subtotal, 2) ?></strong></div>
                    <div class="d-flex justify-content-between"><span class="checkout-muted">Delivery fee</span><strong>&#8369;<?= number_format($deliveryFee, 2) ?></strong></div>
                    <div class="checkout-total d-flex justify-content-between"><span>Total</span><span>&#8369;<?= number_format($total, 2) ?></span></div>
                </div>
            </aside>
        </div>
    </main>
    <script>
    (() => {
        const root = document.querySelector('.checkout-shell');
        const form = document.getElementById('payment-choice-form');
        const button = document.getElementById('place-order-button');
        const status = document.getElementById('checkout-status');
        const csrf = root.dataset.csrf;
        const finalizer = <?= json_encode(app_url('api/orders/finalize_checkout.php')) ?>;
        async function post(url, body) {
            const response = await fetch(url, { method: 'POST', credentials: 'same-origin', headers: {'Content-Type':'application/x-www-form-urlencoded','Accept':'application/json'}, body: body.toString() });
            const data = await response.json();
            if (!response.ok || !data.success) throw new Error(data.message || 'Checkout could not be completed.');
            return data;
        }
        form.addEventListener('submit', async event => {
            event.preventDefault();
            button.disabled = true;
            button.textContent = 'Processing...';
            status.className = 'checkout-status';
            status.textContent = '';
            try {
                const method = new FormData(form).get('payment_method');
                const data = await post(finalizer, new URLSearchParams({csrf_token: csrf, payment_method: method}));
                window.location.assign(data.redirect);
            } catch (error) {
                button.disabled = false;
                button.textContent = 'Place order';
                status.className = 'checkout-status alert alert-danger';
                status.textContent = error.message;
            }
        });
    })();
    </script>
</body>
</html>
