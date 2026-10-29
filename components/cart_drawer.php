<?php
if (empty($_SESSION['cart_csrf_token'])) {
    $_SESSION['cart_csrf_token'] = bin2hex(random_bytes(32));
}
?>
<div class="furnara-cart-layer" id="furnara-cart-layer" hidden>
    <button class="furnara-cart-scrim" type="button" data-cart-close aria-label="Close cart"></button>
    <aside class="furnara-cart-drawer" id="furnara-cart-drawer" role="dialog" aria-modal="true" aria-labelledby="furnara-cart-title">
        <header class="furnara-cart-header"><div><p>Your selection</p><h2 id="furnara-cart-title">Your cart <?php if (!empty($_SESSION['user_id'])): ?><span id="furnara-cart-count">0</span><?php endif; ?></h2></div><button class="furnara-cart-close" type="button" data-cart-close aria-label="Close cart"><i class="bi bi-x-lg"></i></button></header>
        <div class="furnara-cart-items" id="furnara-cart-items" aria-live="polite"><p class="furnara-cart-loading">Loading your cart…</p></div>
        <footer class="furnara-cart-footer"><div class="furnara-cart-total"><span>Subtotal</span><strong id="furnara-cart-total">₱0.00</strong></div><p class="furnara-cart-note">Shipping and any applicable discounts are calculated at checkout.</p><a class="furnara-cart-checkout" id="furnara-cart-checkout" href="<?= !empty($_SESSION['user_id']) ? app_url('pages/public/furniture.php?checkout=1') : app_url('pages/auth/login.php') ?>"><?= !empty($_SESSION['user_id']) ? 'Continue to checkout' : 'Log in to checkout' ?></a><a class="furnara-cart-continue" href="<?= app_url('pages/public/furniture.php') ?>">Continue browsing</a></footer>
    </aside>
</div>
<div id="furnara-cart-config" data-endpoint="<?= app_url('api/cart/manage_cart.php') ?>" data-base-url="<?= APP_BASE_URL ?>" data-login-url="<?= app_url('pages/auth/login.php') ?>" data-csrf-token="<?= htmlspecialchars($_SESSION['cart_csrf_token'], ENT_QUOTES, 'UTF-8') ?>" data-logged-in="<?= !empty($_SESSION['user_id']) ? '1' : '0' ?>" hidden></div>
<script src="<?= app_url('assets/js/cart_drawer.js') ?>" defer></script>
