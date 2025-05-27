<?php
session_start();
require 'config.php'; // this should define $conn

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    die("User not logged in.");
}

$user_id = $_SESSION['user_id'];

// Check if required POST fields exist
if (!isset($_POST['payment_method']) || !isset($_POST['total_amount'])) {
    die("Missing payment method or total amount.");
}

$method = $_POST['payment_method']; // 'instore' or 'delivery'
$total = floatval($_POST['total_amount']); // Ensure it's a float

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start the transaction (optional, since it's already handled inside the procedure)
$conn->begin_transaction();

// Store success status
$success = false;

try {
    // Validate and collect data based on payment method
    if ($method === 'instore') {
        if (isset($_POST['pickup_date']) && isset($_POST['pickup_time'])) {
            $pickup_date = $_POST['pickup_date'];
            $pickup_time = $_POST['pickup_time'];

            // Call stored procedure for instore payment
            $stmt = $conn->prepare("CALL process_checkout(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isdsdssssss", $user_id, $method, $total, $pickup_date, $pickup_time, $dummy_name = null, $dummy_address = null, $dummy_contact = null, $dummy_date = null, $dummy_time = null);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $success = true;
            } else {
                throw new Exception("Error saving in-store payment.");
            }

            $stmt->close();
        } else {
            throw new Exception("Missing pickup date or time.");
        }
    } elseif ($method === 'delivery') {
        if (
            isset($_POST['delivery_name']) &&
            isset($_POST['delivery_address']) &&
            isset($_POST['delivery_contact']) &&
            isset($_POST['delivery_date']) &&
            isset($_POST['delivery_time'])
        ) {
            $delivery_name = $_POST['delivery_name'];
            $delivery_address = $_POST['delivery_address'];
            $delivery_contact = $_POST['delivery_contact'];
            $delivery_date = $_POST['delivery_date'];
            $delivery_time = $_POST['delivery_time'];

            // Call stored procedure for delivery payment
            $stmt = $conn->prepare("CALL process_checkout(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isdsdssssss", $user_id, $method, $total, $dummy_date = null, $dummy_time = null, $delivery_name, $delivery_address, $delivery_contact, $delivery_date, $delivery_time);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $success = true;
            } else {
                throw new Exception("Error saving delivery payment.");
            }

            $stmt->close();
        } else {
            throw new Exception("Missing delivery details.");
        }
    } else {
        throw new Exception("Invalid payment method.");
    }

    // Commit transaction if the procedure succeeded
    $conn->commit();
} catch (Exception $e) {
    // Rollback transaction if any error occurs
    $conn->rollback();
    echo "Checkout failed: " . $e->getMessage();
}

$conn->close();

if ($success) {
    echo "Checkout successful!";
}
