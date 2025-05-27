<?php
include 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo 'User not logged in.';
    exit;
}

$order_id = $_GET['id'];
$order_type = $_GET['type'];

// Validate input
if (empty($order_id) || empty($order_type)) {
    echo 'Invalid order or type.';
    exit;
}

// Start a transaction to ensure both deletions happen together
$conn->begin_transaction();

try {
    if ($order_type === 'instore') {
        // Delete from the instore_payment table
        $delete_payment_sql = "DELETE FROM instore_payment WHERE order_id = ?";
        $delete_payment_stmt = $conn->prepare($delete_payment_sql);
        $delete_payment_stmt->bind_param("i", $order_id);
        $delete_payment_stmt->execute();
        $delete_payment_stmt->close();

        // Delete the corresponding order from the orders table
        $delete_order_sql = "DELETE FROM orders WHERE order_id = ?";
        $delete_order_stmt = $conn->prepare($delete_order_sql);
        $delete_order_stmt->bind_param("i", $order_id);
        $delete_order_stmt->execute();
        $delete_order_stmt->close();
    } elseif ($order_type === 'delivery') {
        // Delete from the delivery_payment table
        $delete_payment_sql = "DELETE FROM delivery_payment WHERE order_id = ?";
        $delete_payment_stmt = $conn->prepare($delete_payment_sql);
        $delete_payment_stmt->bind_param("i", $order_id);
        $delete_payment_stmt->execute();
        $delete_payment_stmt->close();

        // Delete the corresponding order from the orders table
        $delete_order_sql = "DELETE FROM orders WHERE order_id = ?";
        $delete_order_stmt = $conn->prepare($delete_order_sql);
        $delete_order_stmt->bind_param("i", $order_id);
        $delete_order_stmt->execute();
        $delete_order_stmt->close();
    } else {
        throw new Exception("Invalid order type.");
    }

    // Commit the transaction if everything goes well
    $conn->commit();

    // Redirect back to orders page
    header("Location: orders.php");
    exit();
} catch (Exception $e) {
    // Rollback if there is any issue
    $conn->rollback();
    echo "Failed to cancel order: " . $e->getMessage();
}
