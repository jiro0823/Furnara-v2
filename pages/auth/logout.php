<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';

@session_start();
@session_unset();
@session_destroy();

header('Location: ' . app_url('pages/public/user.php'));
exit;
