<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';

if (empty($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Administrator access required.');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['refund_action_token']) || !hash_equals($_SESSION['refund_action_token'], (string)($_POST['csrf_token'] ?? ''))) {
    http_response_code(403);
    exit('Invalid request. Refresh the admin dashboard and try again.');
}
$requestId = filter_var($_POST['request_id'] ?? null, FILTER_VALIDATE_INT);
$status = (string)($_POST['status'] ?? '');
if (!$requestId || !in_array($status, ['Approved', 'Declined'], true)) {
    http_response_code(400);
    exit('Choose a valid refund action.');
}
$statement = $conn->prepare('UPDATE refund_requests SET status = ? WHERE id = ? AND status = \'Pending\'');
$statement->bind_param('si', $status, $requestId);
$statement->execute();
$statement->close();
header('Location: ' . app_url('pages/admin/admin.php'));
exit;
