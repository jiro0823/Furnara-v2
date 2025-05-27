<?php
require 'config.php';

$furnitureId = $_POST['furniture_id'];
$userId = $_POST['user_id'] ?? null;
$sessionId = $_POST['session_id'] ?? null;
$newQuantity = $_POST['quantity'];

if (!$furnitureId || (!$userId && !$sessionId)) {
    http_response_code(400);
    echo "Invalid data.";
    exit;
}

// Check stock availability first
$stock_query = "SELECT stock FROM furniture WHERE furniture_id = ?";
$stmt = $conn->prepare($stock_query);
$stmt->bind_param("i", $furnitureId);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    http_response_code(400);
    echo "Product not found.";
    exit;
}

// For quantity increase, validate against stock
if ($newQuantity > $product['stock']) {
    http_response_code(400);
    echo "Cannot add more items. Only " . $product['stock'] . " units available.";
    exit;
}

if ($newQuantity < 1) {
    // Delete item if quantity is below 1
    $deleteQuery = "DELETE FROM cart_items WHERE furniture_id = ? AND status = 'active' AND " . ($userId ? "user_id = ?" : "session_id = ?");
    $stmt = $conn->prepare($deleteQuery);
    if ($userId) {
        $stmt->bind_param("ii", $furnitureId, $userId);
    } else {
        $stmt->bind_param("is", $furnitureId, $sessionId);
    }
    $stmt->execute();
    echo "Item removed.";
} else {
    // Update quantity if 1 or higher and within stock limit
    $updateQuery = "UPDATE cart_items SET quantity = ? WHERE furniture_id = ? AND status = 'active' AND " . ($userId ? "user_id = ?" : "session_id = ?");
    $stmt = $conn->prepare($updateQuery);
    if ($userId) {
        $stmt->bind_param("iii", $newQuantity, $furnitureId, $userId);
    } else {
        $stmt->bind_param("iis", $newQuantity, $furnitureId, $sessionId);
    }
    $stmt->execute();
    echo "Quantity updated.";
}
