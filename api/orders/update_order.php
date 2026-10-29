<?php
session_start();
require_once dirname(__DIR__, 2) . '/includes/config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo 'User not logged in.';
    exit;
}

$user_id = $_SESSION['user_id'];

// Get the form data
$order_id = $_POST['order_id'];
$order_type = $_POST['order_type'];

if ($order_type == 'instore') {
    $pickup_date = $_POST['pickup_date'];
    $pickup_time = $_POST['pickup_time'];

    // Update in-store pickup order using order_id instead of id
    $sql = "UPDATE instore_payment SET pickup_date = ?, pickup_time = ? WHERE order_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssii", $pickup_date, $pickup_time, $order_id, $user_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo 'Order updated successfully.';
    } else {
        echo 'Failed to update order.';
    }
} elseif ($order_type == 'delivery') {
    $delivery_name = $_POST['delivery_name'];
    $delivery_address = $_POST['delivery_address'];
    $delivery_date = $_POST['delivery_date'];
    $delivery_time = $_POST['delivery_time'];

    // Update delivery order using order_id instead of id
    $sql = "UPDATE delivery_payment SET delivery_name = ?, delivery_address = ?, delivery_date = ?, delivery_time = ? WHERE order_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssii", $delivery_name, $delivery_address, $delivery_date, $delivery_time, $order_id, $user_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo 'Order updated successfully.';
    } else {
        echo 'Failed to update order.';
    }
}

$stmt->close();
$conn->close();
