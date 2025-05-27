<?php
require 'config.php';

$furnitureId = $_POST['furniture_id'];
$quantity = $_POST['quantity'];
$userId = $_POST['user_id'] ?? null;
$sessionId = $_POST['session_id'] ?? null;
$name = $_POST['name'] ?? '';
$price = $_POST['price'] ?? 0;
$image = $_POST['image'] ?? '';
$status = 'active';

if (!$furnitureId || (!$userId && !$sessionId)) {
    http_response_code(400);
    echo "Invalid data.";
    exit;
}

// Check stock availability
$stock_query = "SELECT stock FROM furniture WHERE furniture_id = ?";
$stmt = $conn->prepare($stock_query);
$stmt->bind_param("i", $furnitureId);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product || $product['stock'] <= 0) {
    http_response_code(400);
    echo "Product is out of stock.";
    exit;
}

if ($quantity > $product['stock']) {
    http_response_code(400);
    echo "Cannot add more items than available in stock. Only " . $product['stock'] . " units available.";
    exit;
}

// Check if the item already exists
$checkQuery = "SELECT * FROM cart_items WHERE furniture_id = ? AND status = 'active' AND " . ($userId ? "user_id = ?" : "session_id = ?");
$stmt = $conn->prepare($checkQuery);
if ($userId) {
    $stmt->bind_param("ii", $furnitureId, $userId);
} else {
    $stmt->bind_param("is", $furnitureId, $sessionId);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Update existing quantity
    $updateQuery = "UPDATE cart_items SET quantity = ? WHERE furniture_id = ? AND status = 'active' AND " . ($userId ? "user_id = ?" : "session_id = ?");
    $stmt = $conn->prepare($updateQuery);
    if ($userId) {
        $stmt->bind_param("iii", $quantity, $furnitureId, $userId);
    } else {
        $stmt->bind_param("iis", $quantity, $furnitureId, $sessionId);
    }
    $stmt->execute();
} else {
    // Insert new item
    $insertQuery = "INSERT INTO cart_items (furniture_id, quantity, name, price, image, user_id, session_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')";
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("iisdsss", $furnitureId, $quantity, $name, $price, $image, $userId, $sessionId);
    $stmt->execute();
}

echo "Cart updated successfully.";
