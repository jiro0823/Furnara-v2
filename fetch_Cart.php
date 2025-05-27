<?php
function fetch_Cart($userId, $sessionId)
{
    include 'config.php';

    // Prefer user ID if available, fallback to session ID
    $identifier = $userId ? $userId : $sessionId;

    $query = "SELECT * FROM cart_items WHERE session_id = '$identifier'";
    $result = mysqli_query($conn, $query);

    $cartItems = [];

    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $cartItems[] = $row;
        }
    }

    return $cartItems;
}
