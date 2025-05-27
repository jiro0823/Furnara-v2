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

// Sanitize inputs
$total_amount = isset($_POST['totalAmount']) ? floatval(str_replace(['₱', ','], '', trim($_POST['totalAmount']))) : null;
$name = isset($_POST['deliveryName']) ? trim($_POST['deliveryName']) : '';
$address = isset($_POST['deliveryAddress']) ? trim($_POST['deliveryAddress']) : '';
$contact = isset($_POST['deliveryContact']) ? trim($_POST['deliveryContact']) : '';
$delivery_date = isset($_POST['deliveryDate']) ? trim($_POST['deliveryDate']) : '';
$delivery_time = isset($_POST['deliveryTime']) ? trim($_POST['deliveryTime']) : '';

// Get furniture items from cart
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

// Validation
if (
    $total_amount === null || $name === '' || $address === '' || $contact === '' ||
    $delivery_date === '' || $delivery_time === '' || $furniture_name === ''
) {
    echo "Missing required fields. Debug info: ";
    echo "Amount: " . ($total_amount ?? 'null') . ", Name: $name, Address: $address, ";
    echo "Contact: $contact, Date: $delivery_date, Time: $delivery_time, Items: $furniture_name";
    exit;
}

$created_at = date('Y-m-d H:i:s');

// First, insert into orders table to get order_id
$order_date = date('Y-m-d H:i:s');
$status = 'Pending';
$delivery_type = 'Delivery';

$insert_order_sql = "INSERT INTO orders (user_id, delivery_type, total_price, order_date, status, 
                    furniture_name, total_amount, delivery_name, delivery_address, delivery_contact) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$insert_order_stmt = $conn->prepare($insert_order_sql);
$insert_order_stmt->bind_param(
    "isdsssssss",
    $user_id,
    $delivery_type,
    $total_amount,
    $order_date,
    $status,
    $furniture_name,
    $total_amount,
    $name,
    $address,
    $contact
);

if ($insert_order_stmt->execute()) {
    $order_id = $insert_order_stmt->insert_id;

    // Then insert into delivery_payment with order_id
    $sql = "INSERT INTO delivery_payment (user_id, order_id, total_amount, delivery_name, furniture_name, 
            delivery_address, delivery_contact, delivery_date, delivery_time, created_at, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "iidsssssss",
        $user_id,
        $order_id,
        $total_amount,
        $name,
        $furniture_name,
        $address,
        $contact,
        $delivery_date,
        $delivery_time,
        $created_at
    );

    if ($stmt->execute()) {
        // Clear the cart
        $delete_cart_sql = "DELETE FROM cart_items WHERE user_id = ?";
        $delete_cart_stmt = $conn->prepare($delete_cart_sql);
        $delete_cart_stmt->bind_param("i", $user_id);
        $delete_cart_stmt->execute();

        // Insert into delivery_tracking
        $tracking_status = 'Preparing';
        $insert_tracking_sql = "INSERT INTO delivery_tracking (order_id, user_id, customer_name, 
                              delivery_address, status) VALUES (?, ?, ?, ?, ?)";
        $insert_tracking_stmt = $conn->prepare($insert_tracking_sql);
        $insert_tracking_stmt->bind_param(
            "iisss",
            $order_id,
            $user_id,
            $name,
            $address,
            $tracking_status
        );

        if ($insert_tracking_stmt->execute()) {
            echo "Delivery scheduled successfully and order created.";
        } else {
            echo "Error creating delivery tracking: " . $insert_tracking_stmt->error;
        }
    } else {
        echo "Error creating delivery payment: " . $stmt->error;
    }
} else {
    echo "Error creating order: " . $insert_order_stmt->error;
}
