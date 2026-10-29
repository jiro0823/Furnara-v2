<?php $currentAdminPage = strtolower(basename($_SERVER['SCRIPT_NAME'] ?? '')); ?>
<aside class="sidebar admin-sidebar" aria-label="Administration navigation">
    <h3>Furnara Admin</h3>
    <a class="<?= $currentAdminPage === 'admin.php' ? 'active' : '' ?>" href="<?= app_url('pages/admin/admin.php') ?>"><i class="bi bi-grid-1x2"></i> Overview</a>
    <a class="<?= $currentAdminPage === 'manage_products.php' ? 'active' : '' ?>" href="<?= app_url('pages/admin/manage_products.php') ?>"><i class="bi bi-box-seam"></i> Manage Products</a>
    <a class="<?= $currentAdminPage === 'orders_display.php' ? 'active' : '' ?>" href="<?= app_url('pages/admin/Orders_Display.php') ?>"><i class="bi bi-receipt"></i> Orders</a>
    <a class="<?= $currentAdminPage === 'customer_messages.php' ? 'active' : '' ?>" href="<?= app_url('pages/admin/customer_messages.php') ?>"><i class="bi bi-chat-dots"></i> Customer messages</a>
    <a class="<?= $currentAdminPage === 'delivery_tracking.php' ? 'active' : '' ?>" href="<?= app_url('pages/orders/Delivery_tracking.php') ?>"><i class="bi bi-truck"></i> Tracking</a>
    <a class="<?= $currentAdminPage === 'manage_discounts.php' ? 'active' : '' ?>" href="<?= app_url('pages/admin/manage_discounts.php') ?>"><i class="bi bi-tag"></i> Discounts</a>
    <?php $logoutFormClass = 'admin-sidebar-logout-form'; $logoutButtonClass = 'admin-sidebar-logout-link'; $logoutButtonIcon = 'bi bi-box-arrow-right'; include dirname(__DIR__) . '/actions/logout_button.php'; ?>
</aside>
<?php require dirname(__DIR__) . '/support_chat_widget.php'; ?>
