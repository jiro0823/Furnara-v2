<?php
// Resolve URLs relative to the project so deployment can use either XAMPP or PHP's built-in server.
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}
$documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? APP_ROOT) ?: APP_ROOT;
$appRelativePath = str_replace('\\', '/', substr(APP_ROOT, strlen($documentRoot)));
if (!defined('APP_BASE_URL')) {
    define('APP_BASE_URL', $appRelativePath === '' ? '' : '/' . trim($appRelativePath, '/'));
}
if (!function_exists('app_url')) {
    function app_url(string $path = ''): string
    {
        return APP_BASE_URL . '/' . ltrim($path, '/');
    }
}

// Must come before output or session start
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(0, "/");
    session_start();
}

ob_start();

$dbHost = getenv('FURNARA_DB_HOST') ?: '127.0.0.1';
$dbPort = (int)(getenv('FURNARA_DB_PORT') ?: 3306);
$dbName = getenv('FURNARA_DB_NAME') ?: 'furnaradb';
$dbUser = getenv('FURNARA_DB_USER') ?: 'root';
$dbPassword = getenv('FURNARA_DB_PASSWORD') ?: '';

// Keep database outages from exposing a PHP exception and filesystem paths to visitors.
mysqli_report(MYSQLI_REPORT_OFF);
$conn = @mysqli_connect($dbHost, $dbUser, $dbPassword, $dbName, $dbPort);

if (!$conn) {
    error_log('Furnara database connection failed: ' . mysqli_connect_error());
    http_response_code(503);
    exit('The store database is unavailable. Start MySQL in XAMPP, then refresh this page.');
}

