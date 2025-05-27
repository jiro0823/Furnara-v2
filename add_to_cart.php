<?php
require 'config.php';

if (!isset($_POST['furniture_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$furniture_id = $_POST['furniture_id'];
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$session_id = session_id();

// Check stock availability first
$stock_query = "SELECT stock FROM furniture WHERE furniture_id = ?";
$stmt = $conn->prepare($stock_query);
$stmt->bind_param("i", $furniture_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

if ($product['stock'] <= 0) {
    echo json_encode(['success' => false, 'message' => 'Product is out of stock']);
    exit;
}

// Check if current cart quantity + requested quantity exceeds stock
$cart_query = "SELECT quantity FROM cart_items WHERE furniture_id = ? AND " .
    ($user_id ? "user_id = ?" : "session_id = ?") . " AND status = 'active'";
$stmt = $conn->prepare($cart_query);
if ($user_id) {
    $stmt->bind_param("ii", $furniture_id, $user_id);
} else {
    $stmt->bind_param("is", $furniture_id, $session_id);
}
$stmt->execute();
$cart_result = $stmt->get_result();
$cart_item = $cart_result->fetch_assoc();
$current_cart_quantity = $cart_item ? $cart_item['quantity'] : 0;

if (($current_cart_quantity + $quantity) > $product['stock']) {
    echo json_encode([
        'success' => false,
        'message' => 'Cannot add more items. Only ' . $product['stock'] . ' units available.'
    ]);
    exit;
}

// If we get here, we can proceed with adding to cart
// Rest of your existing cart addition logic
// ...existing code...