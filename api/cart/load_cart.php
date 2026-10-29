<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';
$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;

$cart = [];

if ($userId) {
    $statement = mysqli_prepare($conn, "SELECT * FROM cart_items WHERE user_id = ? AND status = 'active'");
    mysqli_stmt_bind_param($statement, 'i', $userId);
    mysqli_stmt_execute($statement);
    $result = mysqli_stmt_get_result($statement);
    while ($row = mysqli_fetch_assoc($result)) {
        $cart[$row['furniture_id']] = [
            'quantity' => $row['quantity'],
            'name' => $row['name'],
            'price' => $row['price'],
            'image' => $row['image']
        ];
    }
    mysqli_stmt_close($statement);
}
