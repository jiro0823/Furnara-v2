<?php
$_POST['action'] = 'add';
$_POST['quantity'] = max(1, (int)($_POST['quantity'] ?? 1));
require __DIR__ . '/manage_cart.php';
