<?php
require 'config.php';

$furniture_id = $_POST['furniture_id'];
$user_id = $_POST['user_id'];
$session_id = $_POST['session_id'];

try {
    mysqli_begin_transaction($conn);

    // Delete the cart item
    $delete_query = "DELETE FROM cart_items 
                    WHERE furniture_id = '$furniture_id' 
                    AND " . ($user_id ? "user_id = '$user_id'" : "session_id = '$session_id'");

    if (!mysqli_query($conn, $delete_query)) {
        throw new Exception("Error removing item: " . mysqli_error($conn));
    }

    mysqli_commit($conn);
    echo "Item removed successfully";
} catch (Exception $e) {
    mysqli_rollback($conn);
    http_response_code(400);
    echo $e->getMessage();
}
