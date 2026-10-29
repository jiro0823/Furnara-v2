<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';
header('Location: ' . app_url('pages/account/Orders.php?status=in-transit'));
exit;
