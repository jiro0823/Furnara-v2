<?php
$logoutFormClass = $logoutFormClass ?? 'd-inline';
$logoutButtonClass = $logoutButtonClass ?? 'btn btn-outline-dark';
$logoutButtonText = $logoutButtonText ?? 'Log out';
$logoutButtonIcon = $logoutButtonIcon ?? '';
?>
<form action="<?= app_url('pages/auth/logout.php') ?>" method="post" class="<?= htmlspecialchars($logoutFormClass, ENT_QUOTES, 'UTF-8') ?>">
    <button type="submit" class="<?= htmlspecialchars($logoutButtonClass, ENT_QUOTES, 'UTF-8') ?>">
        <?php if ($logoutButtonIcon !== ''): ?><i class="<?= htmlspecialchars($logoutButtonIcon, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i><?php endif; ?>
        <?= htmlspecialchars($logoutButtonText, ENT_QUOTES, 'UTF-8') ?>
    </button>
</form>
