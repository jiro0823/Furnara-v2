<?php
/**
 * Vercel front controller. Static assets are served by Vercel's filesystem
 * handler; PHP page and API requests are dispatched through this function.
 */
$routePath = (string)($_GET['__route'] ?? '/');
unset($_GET['__route']);

$queryString = (string)($_SERVER['QUERY_STRING'] ?? '');
parse_str($queryString, $queryParameters);
unset($queryParameters['__route']);
$queryString = http_build_query($queryParameters);

$requestPath = parse_url($routePath, PHP_URL_PATH);
$requestPath = is_string($requestPath) ? rawurldecode($requestPath) : '/';
$requestPath = '/' . ltrim(str_replace('\\', '/', $requestPath), '/');

if (str_contains($requestPath, "\0") || preg_match('#(?:^|/)\.\.(?:/|$)#', $requestPath)) {
    http_response_code(400);
    exit('Bad Request');
}

$_SERVER['REQUEST_URI'] = $requestPath . ($queryString !== '' ? '?' . $queryString : '');
$_SERVER['QUERY_STRING'] = $queryString;

$legacyRoutes = [
    '/landingpage.php' => '/pages/public/Landingpage.php',
    '/user.php' => '/pages/public/user.php',
    '/furniture.php' => '/pages/public/furniture.php',
    '/login.php' => '/pages/auth/login.php',
    '/logout.php' => '/pages/auth/logout.php',
    '/register.php' => '/pages/auth/register.php',
    '/profile.php' => '/pages/account/profile.php',
    '/orders.php' => '/pages/account/Orders.php',
    '/user_delivery_tracking.php' => '/pages/account/user_delivery_tracking.php',
    '/admin.php' => '/pages/admin/admin.php',
    '/manage_products.php' => '/pages/admin/manage_products.php',
    '/manage_discounts.php' => '/pages/admin/manage_discounts.php',
    '/orders_display.php' => '/pages/admin/Orders_Display.php',
    '/delivery_tracking.php' => '/pages/orders/Delivery_tracking.php',
    '/cancel_order.php' => '/pages/orders/cancel_order.php',
    '/add_to_cart.php' => '/api/cart/add_to_cart.php',
    '/fetch_cart.php' => '/api/cart/fetch_Cart.php',
    '/load_cart.php' => '/api/cart/load_cart.php',
    '/remove_item.php' => '/api/cart/remove_item.php',
    '/save_cart.php' => '/api/cart/save_cart.php',
    '/update_cart_quantity.php' => '/api/cart/update_cart_quantity.php',
    '/save_delivery.php' => '/api/orders/save_delivery.php',
    '/save_instore.php' => '/api/orders/save_instore.php',
    '/update_order.php' => '/api/orders/update_order.php',
];

if ($requestPath === '/') {
    $relativeFile = 'pages/public/user.php';
} elseif (isset($legacyRoutes[strtolower($requestPath)])) {
    $relativeFile = ltrim($legacyRoutes[strtolower($requestPath)], '/');
} elseif (preg_match('#^/(?:pages|api)/[A-Za-z0-9_./-]+\.php$#', $requestPath)) {
    $relativeFile = ltrim($requestPath, '/');
} else {
    http_response_code(404);
    exit('Not Found');
}

$projectRoot = dirname(__DIR__);
$targetFile = realpath($projectRoot . DIRECTORY_SEPARATOR . $relativeFile);
$allowedRoot = realpath($projectRoot);

if ($targetFile === false || $allowedRoot === false || !str_starts_with($targetFile, $allowedRoot . DIRECTORY_SEPARATOR) || !is_file($targetFile)) {
    http_response_code(404);
    exit('Not Found');
}

$_SERVER['SCRIPT_FILENAME'] = $targetFile;
$_SERVER['SCRIPT_NAME'] = '/' . str_replace(DIRECTORY_SEPARATOR, '/', $relativeFile);
$_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];

require $targetFile;
