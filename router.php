<?php
/**
 * Development router for PHP's built-in server.
 * Keeps old top-level URLs working while page and API code lives in feature folders.
 */
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$legacyRoutes = [
    'landingpage.php' => '/pages/public/Landingpage.php',
    'user.php' => '/pages/public/user.php',
    'furniture.php' => '/pages/public/furniture.php',
    'furniture_clone.php' => '/pages/public/furniture_clone.php',
    'login.php' => '/pages/auth/login.php',
    'logout.php' => '/pages/auth/logout.php',
    'register.php' => '/pages/auth/register.php',
    'profile.php' => '/pages/account/profile.php',
    'orders.php' => '/pages/account/Orders.php',
    'user_delivery_tracking.php' => '/pages/account/user_delivery_tracking.php',
    'admin.php' => '/pages/admin/admin.php',
    'manage_products.php' => '/pages/admin/manage_products.php',
    'manage_discounts.php' => '/pages/admin/manage_discounts.php',
    'orders_display.php' => '/pages/admin/Orders_Display.php',
    'delivery_tracking.php' => '/pages/orders/Delivery_tracking.php',
    'cancel_order.php' => '/pages/orders/cancel_order.php',
    'add_to_cart.php' => '/api/cart/add_to_cart.php',
    'fetch_cart.php' => '/api/cart/fetch_Cart.php',
    'load_cart.php' => '/api/cart/load_cart.php',
    'remove_item.php' => '/api/cart/remove_item.php',
    'save_cart.php' => '/api/cart/save_cart.php',
    'update_cart_quantity.php' => '/api/cart/update_cart_quantity.php',
    'save_delivery.php' => '/api/orders/save_delivery.php',
    'save_instore.php' => '/api/orders/save_instore.php',
    'update_order.php' => '/api/orders/update_order.php',
];

if ($path === '/') {
    require __DIR__ . '/pages/public/user.php';
    return true;
}

$legacyName = strtolower(basename($path));
if (isset($legacyRoutes[$legacyName]) && !str_contains($path, '/pages/') && !str_contains($path, '/api/')) {
    require __DIR__ . $legacyRoutes[$legacyName];
    return true;
}

$requestedFile = __DIR__ . $path;
if (is_file($requestedFile)) {
    return false;
}

http_response_code(404);
echo 'Not Found';
return true;
