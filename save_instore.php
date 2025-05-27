<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo 'User not logged in.';
    exit;
}

$user_id = $_SESSION['user_id'];

$total_amount = isset($_POST['totalAmount']) ? floatval(str_replace(['₱', ','], '', trim($_POST['totalAmount']))) : null;
$pickup_date = isset($_POST['pickupDate']) ? trim($_POST['pickupDate']) : '';
$pickup_time = isset($_POST['pickupTime']) ? trim($_POST['pickupTime']) : '';

$furniture_name = '';
$furniture_query = "SELECT name, quantity FROM cart_items WHERE user_id = ?";
$stmt_furniture = $conn->prepare($furniture_query);
$stmt_furniture->bind_param("i", $user_id);
$stmt_furniture->execute();
$result_furniture = $stmt_furniture->get_result();

$furniture_items = [];
while ($row = $result_furniture->fetch_assoc()) {
    $furniture_items[] = $row['name'] . ' x' . $row['quantity'];
}
$furniture_name = implode(', ', $furniture_items);

if ($total_amount === null || $pickup_date === '' || $pickup_time === '' || $furniture_name === '') {
    echo "Missing required fields. Debug info: ";
    echo "Amount: " . ($total_amount ?? 'null') . ", Furniture: $furniture_name, Date: $pickup_date, Time: $pickup_time";
    exit;
}

$created_at = date('Y-m-d H:i:s');

$sql = "INSERT INTO instore_payment (user_id, total_amount, furniture_name, pickup_date, pickup_time, created_at, status)
        VALUES (?, ?, ?, ?, ?, ?, 'Pending')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("idssss", $user_id, $total_amount, $furniture_name, $pickup_date, $pickup_time, $created_at);

if ($stmt->execute()) {
    $instore_payment_id = $stmt->insert_id;

    $delete_cart_sql = "DELETE FROM cart_items WHERE user_id = ?";
    $delete_cart_stmt = $conn->prepare($delete_cart_sql);
    $delete_cart_stmt->bind_param("i", $user_id);
    $delete_cart_stmt->execute();

    $order_date = date('Y-m-d H:i:s');
    $status = 'Pending'; // Define the status value
    $delivery_type = 'In-Store'; // Match the ENUM value in the database

    $insert_order_sql = "INSERT INTO orders (user_id, delivery_type, total_price, order_date, status, furniture_name, total_amount, pickup_date, pickup_time)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $insert_order_stmt = $conn->prepare($insert_order_sql);
    $insert_order_stmt->bind_param("isssssdss", $user_id, $delivery_type, $total_amount, $order_date, $status, $furniture_name, $total_amount, $pickup_date, $pickup_time);

    if ($insert_order_stmt->execute()) {
        $order_id = $insert_order_stmt->insert_id;

        // Update instore_payment with order_id using the correct column name and id
        $update_payment_sql = "UPDATE instore_payment SET order_id = ? WHERE id = ?";
        $update_payment_stmt = $conn->prepare($update_payment_sql);
        $update_payment_stmt->bind_param("ii", $order_id, $instore_payment_id);
        $update_payment_stmt->execute();

        echo "In-store pickup scheduled successfully and order created.";
    } else {
        echo "Error inserting order: " . $insert_order_stmt->error;
    }
} else {
    echo "Error inserting into instore_payment table: " . $stmt->error;
}
