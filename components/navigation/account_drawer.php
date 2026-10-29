<?php
$accountDrawerLoggedIn = !empty($_SESSION['user_id']);
$accountDrawerProfileUrl = app_url('pages/account/profile.php?drawer=1');
?>
<?php if ($accountDrawerLoggedIn): ?>
<div class="account-drawer-anchor" data-account-drawer>
    <button class="account-drawer-trigger" type="button" aria-label="Open your account menu" aria-haspopup="true" aria-expanded="false" aria-controls="account-drawer-menu">
        <i class="bi bi-person" aria-hidden="true"></i>
    </button>
    <section class="account-drawer-menu" id="account-drawer-menu" aria-label="Your account" hidden>
        <header class="account-drawer-header">
            <div><span class="account-drawer-kicker">MY FURNARA</span><strong><?= htmlspecialchars((string)($_SESSION['user_name'] ?? 'Your account'), ENT_QUOTES, 'UTF-8') ?></strong></div>
            <button class="account-drawer-close" type="button" data-account-drawer-close aria-label="Close account menu"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
        </header>
        <nav class="account-drawer-nav" aria-label="Account sections">
            <button type="button" data-account-view="profile" data-url="<?= htmlspecialchars($accountDrawerProfileUrl, ENT_QUOTES, 'UTF-8') ?>"><i class="bi bi-person-circle" aria-hidden="true"></i><span>Profile</span><i class="bi bi-chevron-right" aria-hidden="true"></i></button>
            <button type="button" data-account-view="orders" data-url="<?= htmlspecialchars(app_url('pages/account/Orders.php?drawer=1'), ENT_QUOTES, 'UTF-8') ?>"><i class="bi bi-box-seam" aria-hidden="true"></i><span>Orders &amp; delivery</span><i class="bi bi-chevron-right" aria-hidden="true"></i></button>
            <button type="button" data-account-view="settings" data-url="<?= htmlspecialchars(app_url('pages/account/profile.php?section=settings&drawer=1'), ENT_QUOTES, 'UTF-8') ?>"><i class="bi bi-gear" aria-hidden="true"></i><span>Settings</span><i class="bi bi-chevron-right" aria-hidden="true"></i></button>
            <div class="account-drawer-logout">
                <?php $logoutFormClass = ''; $logoutButtonClass = ''; $logoutButtonText = 'Log out'; $logoutButtonIcon = 'bi bi-box-arrow-right'; include __DIR__ . '/../actions/logout_button.php'; ?>
            </div>
        </nav>
        <section class="account-drawer-content" data-account-content hidden aria-live="polite">
            <header class="account-drawer-content-header"><button type="button" data-account-back><i class="bi bi-arrow-left" aria-hidden="true"></i><span>Account menu</span></button><strong data-account-title></strong></header>
            <iframe title="Account details" data-account-frame loading="lazy" referrerpolicy="same-origin"></iframe>
        </section>
    </section>
</div>
<script>
(() => {
    const root = document.querySelector('[data-account-drawer]');
    if (!root || root.dataset.initialized) return;
    root.dataset.initialized = 'true';
    const trigger = root.querySelector('.account-drawer-trigger');
    const panel = root.querySelector('.account-drawer-menu');
    const content = root.querySelector('[data-account-content]');
    const frame = root.querySelector('[data-account-frame]');
    const title = root.querySelector('[data-account-title]');
    let pinned = false;

    const open = () => {
        panel.hidden = false;
        trigger.setAttribute('aria-expanded', 'true');
        if (window.matchMedia('(max-width: 520px)').matches) {
            root.style.setProperty('--account-drawer-top', `${Math.ceil(root.getBoundingClientRect().bottom + 8)}px`);
        }
    };
    const close = () => {
        panel.hidden = true;
        content.hidden = true;
        trigger.setAttribute('aria-expanded', 'false');
        pinned = false;
    };
    trigger.addEventListener('pointerenter', (event) => { if (event.pointerType === 'mouse' || event.pointerType === 'pen') open(); });
    root.addEventListener('pointerleave', () => { if (!pinned) close(); });
    trigger.addEventListener('click', () => {
        if (panel.hidden) { pinned = true; open(); }
        else if (!pinned) { pinned = true; }
        else close();
    });
    const showView = (button, keepOpen = true) => {
        pinned = keepOpen;
        root.querySelectorAll('[data-account-view]').forEach((item) => item.classList.toggle('is-selected', item === button));
        content.hidden = false;
        title.textContent = button.querySelector('span').textContent;
        const nextUrl = button.dataset.url;
        if (frame.dataset.currentUrl !== nextUrl) {
            frame.dataset.currentUrl = nextUrl;
            frame.src = nextUrl;
        }
    };
    root.querySelectorAll('[data-account-view]').forEach((button) => {
        button.addEventListener('pointerenter', (event) => {
            if (event.pointerType === 'mouse' || event.pointerType === 'pen') showView(button, false);
        });
        button.addEventListener('focus', () => showView(button, true));
        button.addEventListener('click', () => showView(button));
    });
    root.querySelector('[data-account-back]').addEventListener('click', () => {
        content.hidden = true;
    });
    root.querySelector('[data-account-drawer-close]').addEventListener('click', close);
    document.addEventListener('pointerdown', (event) => { if (!root.contains(event.target)) close(); });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !panel.hidden) { close(); trigger.focus(); } });
})();
</script>
<?php else: ?>
<a class="account-drawer-guest" href="<?= app_url('pages/auth/login.php') ?>" aria-label="Log in to your account"><i class="bi bi-person" aria-hidden="true"></i></a>
<?php endif; ?>
