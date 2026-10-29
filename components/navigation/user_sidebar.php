<?php
$accountPage = strtolower(basename($_SERVER['SCRIPT_NAME'] ?? ''));
$accountSection = (string)($_GET['section'] ?? 'profile');
$isProfilePage = $accountPage === 'profile.php';
$isOrdersPage = $accountPage === 'orders.php' || $accountPage === 'user_delivery_tracking.php';
?>
<aside class="account-sidebar" aria-label="Account navigation">
    <a class="account-sidebar-brand" href="<?= app_url('pages/public/user.php') ?>">
        <img src="<?= app_url('pictures/furnara_logo_white.png') ?>" alt="Furnara">
    </a>
    <p class="account-sidebar-label">MY ACCOUNT</p>
    <nav class="account-sidebar-nav">
        <a class="<?= $isProfilePage && $accountSection !== 'settings' ? 'is-active' : '' ?>" href="<?= app_url('pages/account/profile.php') ?>"><i class="bi bi-person-circle"></i><span>Profile</span></a>
        <a class="<?= $isOrdersPage ? 'is-active' : '' ?>" href="<?= app_url('pages/account/Orders.php') ?>"><i class="bi bi-box-seam"></i><span>Orders &amp; delivery</span></a>
        <a class="<?= $isProfilePage && $accountSection === 'settings' ? 'is-active' : '' ?>" href="<?= app_url('pages/account/profile.php?section=settings') ?>"><i class="bi bi-gear"></i><span>Settings</span></a>
    </nav>
    <div class="account-sidebar-bottom">
        <?php $logoutFormClass = 'account-sidebar-logout-form'; $logoutButtonClass = 'account-sidebar-link'; $logoutButtonIcon = 'bi bi-box-arrow-left'; include dirname(__DIR__) . '/actions/logout_button.php'; ?>
        <a class="account-sidebar-link" href="<?= app_url('pages/public/user.php') ?>"><i class="bi bi-arrow-left"></i><span>Back to store</span></a>
    </div>
</aside>
<?php require dirname(__DIR__) . '/support_chat_widget.php'; ?>
