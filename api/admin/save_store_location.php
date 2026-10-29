<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';
header('Content-Type: application/json; charset=utf-8');

function store_location_response(int $status, array $body): never
{
    http_response_code($status);
    echo json_encode($body);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') store_location_response(405, ['ok' => false, 'message' => 'Unsupported request.']);
if (empty($_SESSION['user_id']) || strtolower((string)($_SESSION['user_type'] ?? '')) !== 'admin') {
    store_location_response(403, ['ok' => false, 'message' => 'Administrator access is required.']);
}
$token = (string)($_POST['csrf_token'] ?? '');
if (empty($_SESSION['store_location_csrf']) || !hash_equals($_SESSION['store_location_csrf'], $token)) {
    store_location_response(403, ['ok' => false, 'message' => 'Your session expired. Refresh and try again.']);
}
$address = trim((string)($_POST['address'] ?? ''));
$latitude = filter_var($_POST['latitude'] ?? null, FILTER_VALIDATE_FLOAT);
$longitude = filter_var($_POST['longitude'] ?? null, FILTER_VALIDATE_FLOAT);
if ($address === '' || mb_strlen($address) > 500 || $latitude === false || $longitude === false || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
    store_location_response(422, ['ok' => false, 'message' => 'Enter a store address and valid map pin.']);
}
$value = json_encode(['address' => $address, 'latitude' => (float)$latitude, 'longitude' => (float)$longitude], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
$statement = $conn->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (\'store_location\', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
if (!$statement) store_location_response(500, ['ok' => false, 'message' => 'Store location could not be saved. Apply the delivery map migration first.']);
$statement->bind_param('s', $value);
$success = $statement->execute();
$statement->close();
store_location_response($success ? 200 : 500, ['ok' => $success, 'message' => $success ? 'Store location saved.' : 'Store location could not be saved.']);
