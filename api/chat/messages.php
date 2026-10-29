<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';
header('Content-Type: application/json; charset=utf-8');

function chat_json(array $data, int $status = 200): never {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
$isAdmin = !empty($_SESSION['user_id']) && ($_SESSION['user_type'] ?? '') === 'admin';
$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId < 1) chat_json(['success'=>false,'message'=>'Please log in to use support chat.'], 401);
$csrfKey = $isAdmin ? 'admin_chat_csrf_token' : 'account_csrf_token';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($isAdmin && (($_GET['inbox'] ?? '') === '1')) {
        $inboxSql = "SELECT u.id AS user_id, u.name,
            (SELECT m.message FROM customer_admin_messages m WHERE m.user_id=u.id ORDER BY m.id DESC LIMIT 1) AS last_message,
            (SELECT m.created_at FROM customer_admin_messages m WHERE m.user_id=u.id ORDER BY m.id DESC LIMIT 1) AS last_time,
            (SELECT m.id FROM customer_admin_messages m WHERE m.user_id=u.id ORDER BY m.id DESC LIMIT 1) AS last_message_id,
            (SELECT COUNT(*) FROM customer_admin_messages m WHERE m.user_id=u.id AND m.sender_role='customer' AND m.read_at IS NULL) AS unread_count
            FROM users u WHERE u.user_type IS NULL OR LOWER(u.user_type)<>'admin'
            ORDER BY last_message_id IS NULL ASC, last_message_id DESC, u.name ASC";
        $inbox = $conn->query($inboxSql);
        if (!$inbox) chat_json(['success'=>false,'message'=>'Could not load customer conversations.'], 503);
        $threads = [];
        while ($thread = $inbox->fetch_assoc()) {
            $threads[] = [
                'user_id'=>(int)$thread['user_id'],
                'name'=>$thread['name'],
                'last_message'=>$thread['last_message'] ?? '',
                'last_time'=>$thread['last_time'],
                'last_message_id'=>(int)($thread['last_message_id'] ?? 0),
                'unread_count'=>(int)$thread['unread_count'],
            ];
        }
        chat_json(['success'=>true,'threads'=>$threads]);
    }
    $threadUserId = $isAdmin ? filter_var($_GET['user_id'] ?? null, FILTER_VALIDATE_INT) : $userId;
    if (!$threadUserId || $threadUserId < 1) chat_json(['success'=>false,'message'=>'Choose a conversation.'], 400);
    if ($isAdmin) {
        $userCheck = $conn->prepare("SELECT id FROM users WHERE id=? AND (user_type IS NULL OR LOWER(user_type)<>'admin') LIMIT 1");
        $userCheck->bind_param('i', $threadUserId);
        $userCheck->execute();
        $validCustomer = $userCheck->get_result()->num_rows > 0;
        $userCheck->close();
        if (!$validCustomer) chat_json(['success'=>false,'message'=>'Customer not found.'], 404);
    }
    $readRole = $isAdmin ? 'customer' : 'admin';
    $read = $conn->prepare('UPDATE customer_admin_messages SET read_at=CURRENT_TIMESTAMP WHERE user_id=? AND sender_role=? AND read_at IS NULL');
    if ($read) {
        $read->bind_param('is', $threadUserId, $readRole);
        $read->execute();
        $read->close();
    }
    $statement = $conn->prepare('SELECT id,sender_role,message,created_at FROM customer_admin_messages WHERE user_id=? ORDER BY id DESC LIMIT 250');
    if (!$statement) chat_json(['success'=>false,'message'=>'Support chat is not enabled until the chat database migration is applied.'], 503);
    $statement->bind_param('i', $threadUserId);
    $statement->execute();
    $result = $statement->get_result();
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = ['id'=>(int)$row['id'],'sender_role'=>$row['sender_role'],'message'=>$row['message'],'created_at'=>$row['created_at']];
    }
    $statement->close();
    chat_json(['success'=>true,'messages'=>array_reverse($messages)]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') chat_json(['success'=>false,'message'=>'Unsupported request.'], 405);
if (empty($_SESSION[$csrfKey]) || !hash_equals($_SESSION[$csrfKey], (string)($_POST['csrf_token'] ?? ''))) {
    chat_json(['success'=>false,'message'=>'Your session expired. Refresh this page and try again.'], 403);
}
$threadUserId = $isAdmin ? filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT) : $userId;
$message = trim((string)($_POST['message'] ?? ''));
$message = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $message) ?? '';
if (!$threadUserId || $threadUserId < 1 || $message === '' || mb_strlen($message) > 2000) {
    chat_json(['success'=>false,'message'=>'Write a message up to 2,000 characters.'], 400);
}
if ($isAdmin) {
    $userCheck = $conn->prepare("SELECT id FROM users WHERE id=? AND (user_type IS NULL OR LOWER(user_type)<>'admin') LIMIT 1");
    $userCheck->bind_param('i', $threadUserId);
    $userCheck->execute();
    $validCustomer = $userCheck->get_result()->num_rows > 0;
    $userCheck->close();
    if (!$validCustomer) chat_json(['success'=>false,'message'=>'Customer not found.'], 404);
}
$sender = $isAdmin ? 'admin' : 'customer';
$statement = $conn->prepare('INSERT INTO customer_admin_messages (user_id,sender_role,message) VALUES (?,?,?)');
if (!$statement) chat_json(['success'=>false,'message'=>'Support chat is not enabled until the chat database migration is applied.'], 503);
$statement->bind_param('iss', $threadUserId, $sender, $message);
if (!$statement->execute()) {
    $statement->close();
    chat_json(['success'=>false,'message'=>'Message could not be saved. Please retry.'], 500);
}
$messageId = $statement->insert_id;
$statement->close();
chat_json(['success'=>true,'message'=>['id'=>$messageId,'sender_role'=>$sender,'message'=>$message,'created_at'=>date('Y-m-d H:i:s')]]);
