<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';
header('Content-Type: application/json; charset=utf-8');

function feedback_json(array $payload, int $status = 200): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') feedback_json(['success' => false, 'message' => 'Unsupported request.'], 405);
if (empty($_SESSION['user_id'])) feedback_json(['success' => false, 'message' => 'Please log in to submit feedback.'], 401);
$userId = (int)$_SESSION['user_id'];
if (empty($_SESSION['account_csrf_token']) || !hash_equals($_SESSION['account_csrf_token'], (string)($_POST['csrf_token'] ?? ''))) {
    feedback_json(['success' => false, 'message' => 'Your session expired. Refresh the page and try again.'], 403);
}

$action = (string)($_POST['action'] ?? '');
$orderId = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);
$productId = filter_var($_POST['furniture_id'] ?? null, FILTER_VALIDATE_INT);
$rating = filter_var($_POST['rating'] ?? null, FILTER_VALIDATE_INT);
$text = trim((string)($_POST['text'] ?? ''));
if (!$orderId || !$productId || $orderId < 1 || $productId < 1 || !in_array($action, ['quick_rating', 'review', 'refund'], true)) {
    feedback_json(['success' => false, 'message' => 'Choose a valid order and product.'], 400);
}
if (in_array($action, ['quick_rating', 'review'], true) && (!$rating || $rating < 1 || $rating > 5)) {
    feedback_json(['success' => false, 'message' => 'Choose a rating from 1 to 5 stars.'], 400);
}
if ($action === 'review' && (mb_strlen($text) < 5 || mb_strlen($text) > 1500)) {
    feedback_json(['success' => false, 'message' => 'Write a review between 5 and 1,500 characters.'], 400);
}
if ($action === 'refund' && (mb_strlen($text) < 10 || mb_strlen($text) > 1500)) {
    feedback_json(['success' => false, 'message' => 'Please provide a refund reason between 10 and 1,500 characters.'], 400);
}

$orderStatement = $conn->prepare("SELECT o.furniture_name, COALESCE(NULLIF(dt.status, ''), NULLIF(dp.status, ''), NULLIF(ip.status, ''), o.status) AS order_status
    FROM orders o
    LEFT JOIN delivery_tracking dt ON dt.order_id = o.order_id AND dt.user_id = o.user_id
    LEFT JOIN delivery_payment dp ON dp.order_id = o.order_id AND dp.user_id = o.user_id
    LEFT JOIN instore_payment ip ON ip.order_id = o.order_id AND ip.user_id = o.user_id
    WHERE o.order_id = ? AND o.user_id = ? LIMIT 1");
$orderStatement->bind_param('ii', $orderId, $userId);
$orderStatement->execute();
$order = $orderStatement->get_result()->fetch_assoc();
$orderStatement->close();
if (!$order) feedback_json(['success' => false, 'message' => 'That order was not found in your account.'], 404);
if (!in_array(strtolower((string)$order['order_status']), ['delivered', 'received', 'completed'], true)) {
    feedback_json(['success' => false, 'message' => 'Reviews and refund requests are available after an order is delivered.'], 409);
}

$productStatement = $conn->prepare('SELECT name FROM furniture WHERE furniture_id = ? LIMIT 1');
$productStatement->bind_param('i', $productId);
$productStatement->execute();
$product = $productStatement->get_result()->fetch_assoc();
$productStatement->close();
$belongsToOrder = false;
if ($product) {
    foreach (preg_split('/\s*,\s*/', (string)$order['furniture_name'], -1, PREG_SPLIT_NO_EMPTY) as $item) {
        if (preg_match('/^(.*?)\s+x\d+$/i', $item, $matches) && strcasecmp(trim($matches[1]), (string)$product['name']) === 0) {
            $belongsToOrder = true;
            break;
        }
    }
}
if (!$belongsToOrder) feedback_json(['success' => false, 'message' => 'That product is not part of this order.'], 403);

if ($action === 'refund') {
    $statement = $conn->prepare("INSERT INTO refund_requests (user_id, order_id, furniture_id, reason, status)
        VALUES (?, ?, ?, ?, 'Pending')
        ON DUPLICATE KEY UPDATE reason = VALUES(reason), status = 'Pending', updated_at = CURRENT_TIMESTAMP");
    if (!$statement) feedback_json(['success' => false, 'message' => 'Refund requests are not available until the account feedback migration is applied.'], 503);
    $statement->bind_param('iiis', $userId, $orderId, $productId, $text);
    $ok = $statement->execute();
    $statement->close();
    feedback_json($ok ? ['success' => true, 'message' => 'Your refund request was sent to the Furnara team.'] : ['success' => false, 'message' => 'Could not send the refund request.'], $ok ? 200 : 500);
}

$reviewText = $action === 'review' ? $text : null;
$statement = $conn->prepare("INSERT INTO product_reviews (user_id, order_id, furniture_id, rating, review_text)
    VALUES (?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE rating = VALUES(rating), review_text = IF(VALUES(review_text) IS NULL, review_text, VALUES(review_text)), updated_at = CURRENT_TIMESTAMP");
if (!$statement) feedback_json(['success' => false, 'message' => 'Product ratings are not available until the account feedback migration is applied.'], 503);
$statement->bind_param('iiiis', $userId, $orderId, $productId, $rating, $reviewText);
$ok = $statement->execute();
$statement->close();
feedback_json($ok ? ['success' => true, 'message' => $action === 'review' ? 'Your review has been saved. Thank you for sharing your feedback.' : 'Your rating has been saved.'] : ['success' => false, 'message' => 'Could not save your rating.'], $ok ? 200 : 500);
