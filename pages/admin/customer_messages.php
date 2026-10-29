<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';
if (empty($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    header('Location: ' . app_url('pages/auth/login.php'));
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Customer Messages | Furnara Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= app_url('assets/css/admin_sidebar.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/admin_messages.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/support_chat_widget.css') ?>">
</head>
<body>
<?php require dirname(__DIR__, 2) . '/components/navigation/admin_sidebar.php'; ?>
<main class="main-content admin-messages-main">
    <header class="admin-message-heading">
        <div><p>FURNARA / CUSTOMER CARE</p><h1>Customer messages</h1><span>Private conversations are separated by customer account.</span></div>
        <a href="<?= app_url('pages/admin/admin.php') ?>"><i class="bi bi-arrow-left"></i> Dashboard</a>
    </header>
    <section class="admin-chat-no-selection">
        <i class="bi bi-chat-square-heart"></i>
        <h2>Support conversations</h2>
        <p>Open the fixed chat button in the lower-right corner, then choose a customer to read or reply. Messages are saved to that customer’s account and refresh automatically.</p>
        <button class="admin-chat-open-button" type="button" onclick="document.querySelector('[data-support-open]')?.click()"><i class="bi bi-chat-dots-fill"></i> Open support chat</button>
    </section>
</main>
</body>
</html>
