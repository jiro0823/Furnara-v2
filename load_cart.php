<?php
require 'config.php';
$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;

if (!isset($_SESSION['session_id'])) {
    $_SESSION['session_id'] = session_id();
}
$sessionId = $_SESSION['session_id'];

$cart = [];

if ($userId) {
    $query = "SELECT * FROM cart_items WHERE user_id = '$userId' AND status = 'active'";
} else {
    $query = "SELECT * FROM cart_items WHERE session_id = '$sessionId' AND status = 'active'";
}

$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $cart[$row['furniture_id']] = [
            'quantity' => $row['quantity'],
            'name' => $row['name'],
            'price' => $row['price'],
            'image' => $row['image']
        ];
    }
}
