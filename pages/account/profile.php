<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ' . app_url('pages/auth/login.php'));
    exit;
}
$userId = (int)$_SESSION['user_id'];
$isDrawerView = ($_GET['drawer'] ?? '') === '1';
if (empty($_SESSION['account_csrf_token'])) $_SESSION['account_csrf_token'] = bin2hex(random_bytes(32));
$csrfToken = $_SESSION['account_csrf_token'];
$section = ($_GET['section'] ?? '') === 'settings' ? 'settings' : 'profile';
$notice = $_SESSION['account_notice'] ?? null;
unset($_SESSION['account_notice']);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_account_settings'])) {
    if (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
        $error = 'Your session expired. Refresh the page and try again.';
    } else {
        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $currentPassword = (string)($_POST['current_password'] ?? '');
        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Enter a name and a valid email address.';
        } else {
            $emailCheck = $conn->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
            $emailCheck->bind_param('si', $email, $userId);
            $emailCheck->execute();
            $emailTaken = $emailCheck->get_result()->num_rows > 0;
            $emailCheck->close();

            if ($emailTaken) {
                $error = 'That email address is already in use.';
            } elseif ($newPassword !== '' && (strlen($newPassword) < 8 || $newPassword !== $confirmPassword || $currentPassword === '')) {
                $error = 'To change your password, provide your current password, and enter matching new passwords with at least 8 characters.';
            } else {
                $update = $conn->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
                $update->bind_param('i', $userId);
                $update->execute();
                $account = $update->get_result()->fetch_assoc();
                $update->close();

                $passwordValid = $account && (password_verify($currentPassword, $account['password']) || md5($currentPassword) === $account['password']);
                if ($newPassword !== '' && !$passwordValid) {
                    $error = 'Your current password is incorrect.';
                } else {
                    if ($newPassword !== '') {
                        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                        $update = $conn->prepare('UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?');
                        $update->bind_param('sssi', $name, $email, $passwordHash, $userId);
                    } else {
                        $update = $conn->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?');
                        $update->bind_param('ssi', $name, $email, $userId);
                    }
                    if ($update->execute()) {
                        $_SESSION['user_name'] = $name;
                        $_SESSION['account_notice'] = 'Your account settings have been saved.';
                        $update->close();
                        header('Location: ' . app_url('pages/account/profile.php?section=settings' . ($isDrawerView ? '&drawer=1' : '')));
                        exit;
                    }
                    $error = 'Your settings could not be saved. Please try again.';
                    $update->close();
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_delivery_location'])) {
    $address = trim((string)($_POST['delivery_address'] ?? ''));
    $latitude = filter_var($_POST['delivery_latitude'] ?? null, FILTER_VALIDATE_FLOAT);
    $longitude = filter_var($_POST['delivery_longitude'] ?? null, FILTER_VALIDATE_FLOAT);
    if (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
        $error = 'Your session expired. Refresh the page and try again.';
    } elseif ($address === '' || mb_strlen($address) > 500 || $latitude === false || $longitude === false || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
        $error = 'Enter an address and select a valid map location.';
    } else {
        $saveLocation = $conn->prepare('UPDATE users SET delivery_address = ?, delivery_latitude = ?, delivery_longitude = ? WHERE id = ?');
        if ($saveLocation) {
            $saveLocation->bind_param('sddi', $address, $latitude, $longitude, $userId);
            if ($saveLocation->execute()) {
                $_SESSION['account_notice'] = 'Your delivery address and map pin have been saved.';
                $saveLocation->close();
                header('Location: ' . app_url('pages/account/profile.php?section=settings' . ($isDrawerView ? '&drawer=1' : '')));
                exit;
            }
            $saveLocation->close();
        }
        $error = 'Your delivery location could not be saved. Please try again.';
    }
    $section = 'settings';
}

$userStatement = $conn->prepare('SELECT id, name, email, user_type, delivery_address, delivery_latitude, delivery_longitude FROM users WHERE id = ? LIMIT 1');
$userStatement->bind_param('i', $userId);
$userStatement->execute();
$user = $userStatement->get_result()->fetch_assoc();
$userStatement->close();
if (!$user) {
    session_destroy();
    header('Location: ' . app_url('pages/auth/login.php'));
    exit;
}
$initials = strtoupper(mb_substr((string)$user['name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $section === 'settings' ? 'Account Settings' : 'My Profile' ?> | Furnara</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= app_url('assets/css/user_sidebar.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/account_pages.css') ?>">
    <?php if ($section === 'settings'): ?><link rel="stylesheet" href="https://unpkg.com/maplibre-gl@5.6.2/dist/maplibre-gl.css"><?php endif; ?>
    <?php if ($isDrawerView): ?><link rel="stylesheet" href="<?= app_url('assets/css/account_drawer_embedded.css') ?>"><?php endif; ?>
    <link rel="stylesheet" href="<?= app_url('assets/css/support_chat_widget.css') ?>">
</head>
<body class="account-page<?= $isDrawerView ? ' account-embedded' : '' ?>">
<div class="account-shell">
    <?php if (!$isDrawerView) require dirname(__DIR__, 2) . '/components/navigation/user_sidebar.php'; ?>
    <main class="account-main">
        <div class="account-content">
            <p class="account-eyebrow">Your Furnara account</p>
            <h1 class="account-page-title"><?= $section === 'settings' ? 'Account settings' : 'Your profile' ?></h1>
            <p class="account-page-subtitle"><?= $section === 'settings' ? 'Manage your sign-in details and personal information.' : 'Your account information and order history, all in one place.' ?></p>

            <?php if ($notice): ?><div class="alert alert-success account-alert"><?= htmlspecialchars($notice) ?></div><?php endif; ?>
            <?php if ($error !== ''): ?><div class="alert alert-danger account-alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <?php if ($section === 'settings'): ?>
                <form class="account-card account-settings-form" method="post" action="<?= app_url('pages/account/profile.php?section=settings' . ($isDrawerView ? '&drawer=1' : '')) ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <div class="mb-4"><h2 class="h5 mb-1">Personal details</h2><p class="text-secondary small mb-0">Keep your account contact information up to date.</p></div>
                    <div class="row g-3">
                        <div class="col-md-6"><label for="account-name">Full name</label><input class="form-control" id="account-name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required maxlength="120"></div>
                        <div class="col-md-6"><label for="account-email">Email address</label><input class="form-control" id="account-email" type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required maxlength="190"></div>
                    </div>
                    <hr class="my-4">
                    <div class="mb-3"><h2 class="h5 mb-1">Delivery location</h2><p class="text-secondary small mb-0">Search your address or place the pin on the map. You can adjust it any time.</p></div>
                    <div class="account-location-picker" data-location-picker>
                        <div class="account-location-search"><input class="form-control" type="search" data-location-search placeholder="Search for your delivery address" autocomplete="off"><button class="btn account-primary-button" type="button" data-location-search-button>Find</button></div>
                        <div class="account-location-map" data-location-map data-lat="<?= htmlspecialchars((string)($user['delivery_latitude'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-lng="<?= htmlspecialchars((string)($user['delivery_longitude'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" aria-label="Choose delivery location on map"></div>
                        <label for="delivery-address">Delivery address</label><input class="form-control" id="delivery-address" name="delivery_address" data-location-address value="<?= htmlspecialchars((string)($user['delivery_address'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required maxlength="500">
                        <div class="row g-3 mt-1"><div class="col-md-6"><label for="delivery-latitude">Latitude</label><input class="form-control" id="delivery-latitude" name="delivery_latitude" data-location-latitude type="number" min="-90" max="90" step="any" value="<?= htmlspecialchars((string)($user['delivery_latitude'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required></div><div class="col-md-6"><label for="delivery-longitude">Longitude</label><input class="form-control" id="delivery-longitude" name="delivery_longitude" data-location-longitude type="number" min="-180" max="180" step="any" value="<?= htmlspecialchars((string)($user['delivery_longitude'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required></div></div>
                        <small class="text-secondary" data-location-message>Map © CARTO · Search © OpenStreetMap contributors · Routing © OSRM</small>
                    </div>
                    <div class="d-flex justify-content-end mt-3"><button class="btn account-primary-button" type="submit" name="save_delivery_location" value="1"><i class="bi bi-geo-alt me-2"></i>Save delivery location</button></div>
                    <hr class="my-4">
                    <div class="mb-3"><h2 class="h5 mb-1">Change password</h2><p class="text-secondary small mb-0">Leave these fields blank to keep your current password.</p></div>
                    <div class="row g-3">
                        <div class="col-md-4"><label for="current-password">Current password</label><input class="form-control" id="current-password" type="password" name="current_password" autocomplete="current-password"></div>
                        <div class="col-md-4"><label for="new-password">New password</label><input class="form-control" id="new-password" type="password" name="new_password" minlength="8" autocomplete="new-password"></div>
                        <div class="col-md-4"><label for="confirm-password">Confirm new password</label><input class="form-control" id="confirm-password" type="password" name="confirm_password" minlength="8" autocomplete="new-password"></div>
                    </div>
                    <div class="d-flex justify-content-end mt-4"><button class="btn account-primary-button" type="submit" name="save_account_settings" value="1"><i class="bi bi-check2 me-2"></i>Save settings</button></div>
                </form>
            <?php else: ?>
                <section class="account-card account-profile-hero">
                    <div class="account-avatar" aria-hidden="true"><?= htmlspecialchars($initials) ?></div>
                    <div><p class="account-eyebrow mb-1">Welcome back</p><h2 class="h3 mb-1"><?= htmlspecialchars($user['name']) ?></h2><p class="text-secondary mb-0">Furnara member</p></div>
                    <a class="btn btn-outline-dark rounded-pill ms-auto" href="<?= app_url('pages/account/profile.php?section=settings' . ($isDrawerView ? '&drawer=1' : '')) ?>"><i class="bi bi-pencil me-2"></i>Edit account</a>
                </section>
                <div class="account-profile-grid">
                    <section class="account-card account-info-cell"><span>Full name</span><strong><?= htmlspecialchars($user['name']) ?></strong></section>
                    <section class="account-card account-info-cell"><span>Email address</span><strong><?= htmlspecialchars($user['email']) ?></strong></section>
                    <section class="account-card account-info-cell"><span>Account type</span><strong><?= htmlspecialchars(ucfirst((string)$user['user_type'])) ?></strong></section>
                    <section class="account-card account-info-cell"><span>Account security</span><strong><i class="bi bi-shield-check text-success me-1"></i>Password protected</strong></section>
                </div>
                <div class="d-flex justify-content-end mt-4"><a class="btn account-primary-button" href="<?= app_url('pages/account/Orders.php' . ($isDrawerView ? '?drawer=1' : '')) ?>"><i class="bi bi-box-seam me-2"></i>View orders and delivery</a></div>
            <?php endif; ?>
        </div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if ($section === 'settings'): ?><script src="https://unpkg.com/maplibre-gl@5.6.2/dist/maplibre-gl.js"></script><script src="<?= app_url('assets/js/location_maps.js') ?>"></script><?php endif; ?>
</body>
</html>
