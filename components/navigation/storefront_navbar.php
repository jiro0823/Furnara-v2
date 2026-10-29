<?php
$currentPage = strtolower(basename($_SERVER['SCRIPT_NAME'] ?? ''));
$isHomePage = in_array($currentPage, ['user.php', 'landingpage.php'], true);
$isFurniturePage = in_array($currentPage, ['furniture.php', 'furniture_clone.php'], true);
?>
<nav class="storefront-nav shadow-sm navbar navbar-expand-lg bg-light" aria-label="Main navigation">
    <div class="container">
        <a class="navbar-brand" href="<?= app_url('pages/public/user.php') ?>" aria-label="Furnara home">
            <img src="<?= app_url('pictures/furnara_header_logo.png') ?>" alt="Furnara" class="storefront-nav-logo">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#storefrontNavbar" aria-controls="storefrontNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="storefrontNavbar">
            <ul class="gap-5 mx-auto navbar-nav">
                <li class="nav-item"><a class="nav-link<?= $isHomePage ? ' active' : '' ?>" href="<?= app_url('pages/public/user.php') ?>"<?= $isHomePage ? ' aria-current="page"' : '' ?>>Home</a></li>
                <li class="nav-item"><a class="nav-link<?= $isFurniturePage ? ' active' : '' ?>" href="<?= app_url('pages/public/furniture.php') ?>"<?= $isFurniturePage ? ' aria-current="page"' : '' ?>>Furniture</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= app_url('pages/public/user.php#designer-directory') ?>">Designers</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= app_url('pages/public/user.php#about-brand-story') ?>">About Us</a></li>
            </ul>
            <div class="d-flex align-items-center gap-3 storefront-nav-actions">
                <a href="<?= app_url('pages/public/furniture.php') ?>" class="text-dark fs-5" aria-label="Browse furniture"><i class="bi bi-search"></i></a>
                <a href="#furnara-cart-drawer" class="text-dark fs-5 position-relative" id="cart-icon" data-cart-open aria-label="Open shopping cart">
                    <i class="bi bi-cart"></i>
                    <?php if (!empty($_SESSION['user_id'])): ?><span id="cart-count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">0</span><?php endif; ?>
                </a>
                <?php require __DIR__ . '/account_drawer.php'; ?>
                <?php if (!empty($_SESSION['user_id'])): ?>
                    <?php /* Logout is included in the account drawer. */ ?>
                <?php else: ?>
                    <a href="<?= app_url('pages/auth/login.php') ?>" class="btn btn-outline-dark">Log in</a>
                    <a href="<?= app_url('pages/auth/register.php') ?>" class="btn btn-warning">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
<?php require dirname(__DIR__) . '/cart_drawer.php'; ?>
<?php require dirname(__DIR__) . '/support_chat_widget.php'; ?>
