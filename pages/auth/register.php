<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';

$error = [];
if (empty($_SESSION['register_csrf_token'])) $_SESSION['register_csrf_token'] = bin2hex(random_bytes(32));
$registerToken = $_SESSION['register_csrf_token'];

if (isset($_POST['submit'])) {
    $name = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $pass = (string)($_POST['password'] ?? '');
    $cpass = (string)($_POST['cpassword'] ?? '');
    $address = trim((string)($_POST['delivery_address'] ?? ''));
    $latitude = filter_var($_POST['delivery_latitude'] ?? null, FILTER_VALIDATE_FLOAT);
    $longitude = filter_var($_POST['delivery_longitude'] ?? null, FILTER_VALIDATE_FLOAT);
    $postedToken = (string)($_POST['csrf_token'] ?? '');

    if (!hash_equals($registerToken, $postedToken)) $error[] = 'Your session expired. Refresh the page and try again.';
    if ($name === '' || mb_strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL)) $error[] = 'Enter a valid name and email address.';
    if (strlen($pass) < 8 || $pass !== $cpass) $error[] = 'Use a password with at least 8 characters and confirm it correctly.';
    if ($address === '' || mb_strlen($address) > 500 || $latitude === false || $longitude === false || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
        $error[] = 'Enter your delivery address and valid map coordinates.';
    }

    if (!$error) {
        $check = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        if (!$check) {
            $error[] = 'Registration is temporarily unavailable.';
        } else {
            $check->bind_param('s', $email);
            $check->execute();
            $exists = $check->get_result()->num_rows > 0;
            $check->close();
            if ($exists) $error[] = 'An account with that email already exists.';
        }
    }

    if (!$error) {
        $passwordHash = password_hash($pass, PASSWORD_DEFAULT);
        // Public registration always creates a customer account; admin roles are assigned separately.
        $insert = $conn->prepare("INSERT INTO users (name, email, password, user_type, delivery_address, delivery_latitude, delivery_longitude) VALUES (?, ?, ?, 'user', ?, ?, ?)");
        if ($insert) {
            $insert->bind_param('ssssdd', $name, $email, $passwordHash, $address, $latitude, $longitude);
            if ($insert->execute()) {
                $insert->close();
                unset($_SESSION['register_csrf_token']);
                header('Location: ' . app_url('pages/auth/login.php?registered=1'));
                exit;
            }
            $insert->close();
        }
        $error[] = 'Your account could not be created. Please try again.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Now</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= app_url('assets/css/register_Style.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/components/site_components.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/support_chat_widget.css') ?>">
    <link rel="stylesheet" href="https://unpkg.com/maplibre-gl@5.6.2/dist/maplibre-gl.css">
</head>

<body>

    <div class="form-container">
        <form action="" method="post">
            <h3>Register Now</h3>
            <?php foreach ($error as $err): ?><span class="error-msg"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></span><?php endforeach; ?>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($registerToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="text" name="name" required placeholder="Enter your name">
            <input type="email" name="email" required placeholder="Enter your email">
            <input type="password" name="password" required placeholder="Enter your password">
            <input type="password" name="cpassword" required placeholder="Confirm your password">
            <section class="register-location-panel">
                <h4>Delivery address</h4>
                <p>Search your address or place the pin on the map. You can update it later in your account.</p>
                <div class="account-location-picker" data-location-picker>
                    <div class="account-location-search"><input type="search" data-location-search placeholder="Search your address" autocomplete="off"><button type="button" data-location-search-button>Find</button></div>
                    <div class="account-location-map" data-location-map aria-label="Choose delivery location on map"></div>
                    <input type="text" name="delivery_address" data-location-address required maxlength="500" placeholder="Enter your complete delivery address">
                    <div class="register-coordinate-fields">
                        <label>Latitude<input type="number" name="delivery_latitude" data-location-latitude required min="-90" max="90" step="any" placeholder="Select a map location"></label>
                        <label>Longitude<input type="number" name="delivery_longitude" data-location-longitude required min="-180" max="180" step="any" placeholder="Select a map location"></label>
                    </div>
                    <small data-location-message>Map © CARTO · Search © OpenStreetMap contributors · Routing © OSRM</small>
                </div>
            </section>
            <input type="submit" name="submit" value="Register Now" class="form-btn">
            <p>Already have an account? <a href="<?= app_url('pages/auth/login.php') ?>">Login now</a></p>
        </form>
    </div>
    <?php require dirname(__DIR__, 2) . '/components/support_chat_widget.php'; ?>
    <script src="https://unpkg.com/maplibre-gl@5.6.2/dist/maplibre-gl.js"></script>
    <script src="<?= app_url('assets/js/location_maps.js') ?>"></script>
</body>

</html>
