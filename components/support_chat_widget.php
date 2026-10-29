<?php
$supportUserId = (int)($_SESSION['user_id'] ?? 0);
$supportIsAdmin = $supportUserId > 0 && (($_SESSION['user_type'] ?? '') === 'admin');
$supportChatToken = '';
if ($supportUserId > 0) {
    $supportTokenKey = $supportIsAdmin ? 'admin_chat_csrf_token' : 'account_csrf_token';
    if (empty($_SESSION[$supportTokenKey])) $_SESSION[$supportTokenKey] = bin2hex(random_bytes(32));
    $supportChatToken = $_SESSION[$supportTokenKey];
}
?>
<div class="support-widget" data-support-widget data-chat-endpoint="<?= app_url('api/chat/messages.php') ?>" data-chat-role="<?= $supportIsAdmin ? 'admin' : 'customer' ?>" data-chat-user-id="" data-chat-csrf="<?= htmlspecialchars($supportChatToken, ENT_QUOTES, 'UTF-8') ?>" data-chat-customer-name="<?= htmlspecialchars((string)($_SESSION['user_name'] ?? 'You'), ENT_QUOTES, 'UTF-8') ?>">
    <section class="support-widget-panel" data-support-panel aria-label="Furnara support chat" hidden>
        <header class="support-widget-header">
            <span class="support-widget-avatar"><i class="bi bi-chat-heart-fill"></i></span>
            <div class="support-widget-heading"><strong><?= $supportIsAdmin ? 'Customer support' : 'Chat with Furnara' ?></strong><span><i></i> <?= $supportIsAdmin ? 'All customer conversations' : 'We are here to help' ?></span></div>
            <button class="support-widget-close" type="button" data-support-close aria-label="Close support chat"><i class="bi bi-x-lg"></i></button>
        </header>
        <?php if ($supportUserId < 1): ?>
            <div class="support-widget-login"><i class="bi bi-lock"></i><p>Log in to start a private conversation with Furnara support.</p><a href="<?= app_url('pages/auth/login.php') ?>">Log in</a></div>
        <?php elseif ($supportIsAdmin): ?>
            <div class="support-widget-inbox" data-support-inbox>
                <label class="support-widget-search"><i class="bi bi-search"></i><input type="search" data-support-search placeholder="Search customers or messages" aria-label="Search customer conversations"></label>
                <div class="support-widget-thread-list" data-support-thread-list><p class="support-widget-empty">Loading conversations…</p></div>
            </div>
            <section class="support-widget-thread-view" data-support-thread-view hidden>
                <header class="support-widget-thread-header"><button type="button" data-support-back aria-label="Back to all conversations"><i class="bi bi-arrow-left"></i></button><div><strong data-support-thread-name>Customer conversation</strong><span data-support-thread-id></span></div></header>
                <div class="support-widget-messages" data-support-messages aria-live="polite"><p class="support-widget-empty">Select a conversation to view messages.</p></div>
                <p class="support-widget-status" data-support-status>Messages are saved to the selected customer account.</p>
                <form class="support-widget-form" data-support-form><input name="message" type="text" maxlength="2000" placeholder="Reply to this customer…" aria-label="Reply to this customer" required><button type="submit" aria-label="Send message"><i class="bi bi-send-fill"></i></button></form>
            </section>
        <?php else: ?>
            <div class="support-widget-messages" data-support-messages aria-live="polite"><p class="support-widget-empty">Loading conversation…</p></div>
            <p class="support-widget-status" data-support-status>Messages are saved to your account.</p>
            <form class="support-widget-form" data-support-form><input name="message" type="text" maxlength="2000" placeholder="Write a message…" aria-label="Write a message" required><button type="submit" aria-label="Send message"><i class="bi bi-send-fill"></i></button></form>
        <?php endif; ?>
    </section>
    <button class="support-widget-launcher" type="button" data-support-open aria-label="Open Furnara support chat" aria-expanded="false"><i class="bi bi-chat-dots-fill"></i><span>Chat with us</span></button>
</div>
<script src="<?= app_url('assets/js/support_chat_widget.js') ?>" defer></script>
