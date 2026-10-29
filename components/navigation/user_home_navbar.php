<?php
$navFurnitureUrl = app_url('pages/public/furniture.php');
?>
<div class="home-promo-bar" aria-label="Store announcements">
    <a href="<?= $navFurnitureUrl ?>?sort=latest">Discover the latest Furnara designs <span aria-hidden="true">→</span></a>
    <a href="<?= $navFurnitureUrl ?>?sale=1">Explore furniture on sale <span aria-hidden="true">→</span></a>
</div>
<nav class="user-home-nav" aria-label="Main navigation">
    <div class="user-home-nav-inner">
        <a class="user-home-brand" href="<?= app_url('pages/public/user.php') ?>" aria-label="Furnara home">
            <img src="<?= app_url('pictures/furnara_header_logo.png') ?>" alt="Furnara">
        </a>
        <button class="user-home-nav-toggle" type="button" aria-expanded="false" aria-controls="user-home-nav-links" aria-label="Toggle navigation">
            <span></span><span></span><span></span>
        </button>
        <div class="user-home-nav-links" id="user-home-nav-links">
            <ul class="user-home-menu">
                <li class="user-home-menu-item user-home-dropdown">
                    <details>
                        <summary>Living Space</summary>
                        <div class="user-home-dropdown-panel user-home-dropdown-list">
                            <a href="<?= $navFurnitureUrl ?>?collection=all-living-space">All Living Space</a>
                            <a href="<?= $navFurnitureUrl ?>?collection=leather-sofas">Leather Sofa</a>
                            <a href="<?= $navFurnitureUrl ?>?collection=fabric-sofas">Fabric Sofas</a>
                            <a href="<?= $navFurnitureUrl ?>?collection=accent-chairs">Accent Chairs</a>
                            <a href="<?= $navFurnitureUrl ?>?collection=center-tables">Center Tables</a>
                            <a href="<?= $navFurnitureUrl ?>?collection=side-tables">Side Tables</a>
                            <a href="<?= $navFurnitureUrl ?>?collection=desks-console-tables">Desks and Console Tables</a>
                            <a href="<?= $navFurnitureUrl ?>?collection=tv-cabinets-storage">TV Cabinets and Storage</a>
                            <a href="<?= $navFurnitureUrl ?>?collection=art-accessories">Art and Accessories</a>
                        </div>
                    </details>
                </li>
                <li class="user-home-menu-item user-home-dropdown user-home-dining-dropdown">
                    <details>
                        <summary>Dining</summary>
                        <div class="user-home-dropdown-panel user-home-dining-panel">
                            <a href="<?= $navFurnitureUrl ?>?collection=all-dining">All Dining</a>
                            <a href="<?= $navFurnitureUrl ?>?collection=dining-sets">Dining Sets</a>
                            <a href="<?= $navFurnitureUrl ?>?collection=dining-tables">Dining Tables</a>
                            <a href="<?= $navFurnitureUrl ?>?collection=dining-chairs">Dining Chairs</a>
                            <a href="<?= $navFurnitureUrl ?>?collection=sideboards">Sideboards</a>
                            <a href="<?= $navFurnitureUrl ?>?collection=barstools-bar-tables">Barstools and Bar Tables</a>
                        </div>
                    </details>
                </li>
                <li class="user-home-menu-item user-home-dropdown">
                    <details>
                        <summary>Sleepshop</summary>
                        <div class="user-home-dropdown-panel user-home-dropdown-list">
                            <a href="<?= $navFurnitureUrl ?>?collection=beds">Beds</a>
                            <a href="<?= $navFurnitureUrl ?>?collection=bedroom-furniture">Bedroom Furniture</a>
                        </div>
                    </details>
                </li>
                <li class="user-home-menu-item"><a href="<?= $navFurnitureUrl ?>?collection=la-z-boy">La-Z-Boy</a></li>
                <li class="user-home-menu-item user-home-dropdown">
                    <details>
                        <summary>Office</summary>
                        <div class="user-home-dropdown-panel user-home-dropdown-list">
                            <a href="<?= $navFurnitureUrl ?>?collection=office-desks">Desks</a>
                            <a href="<?= $navFurnitureUrl ?>?collection=office-chairs">Office Chairs</a>
                            <a href="<?= $navFurnitureUrl ?>?collection=office-storage">Storage</a>
                        </div>
                    </details>
                </li>
                <li class="user-home-menu-item"><a href="<?= $navFurnitureUrl ?>?collection=clearance">Clearance</a></li>
                <li class="user-home-menu-item"><a href="<?= app_url('pages/public/user.php#footer-contact-navigation') ?>">Stores</a></li>
                <li class="user-home-menu-item"><a href="<?= app_url('pages/public/user.php#sustainability-and-perspectives') ?>">Blogs</a></li>
            </ul>
            <div class="user-home-nav-actions">
                <a href="<?= $navFurnitureUrl ?>" aria-label="Search furniture"><i class="bi bi-search" aria-hidden="true"></i></a>
                <a href="#furnara-cart-drawer" id="cart-icon" data-cart-open aria-label="Open shopping cart">
                    <i class="bi bi-bag" aria-hidden="true"></i><?php if (!empty($_SESSION['user_id'])): ?><span id="cart-count">0</span><?php endif; ?>
                </a>
                <?php require __DIR__ . '/account_drawer.php'; ?>
                <?php if (!empty($_SESSION['user_id'])): ?>
                    <?php /* Logout is included in the account drawer. */ ?>
                <?php else: ?>
                    <a class="user-home-account-link" href="<?= app_url('pages/auth/login.php') ?>">Log in</a>
                    <a class="user-home-account-link" href="<?= app_url('pages/auth/register.php') ?>">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
<?php require dirname(__DIR__) . '/cart_drawer.php'; ?>
<?php require dirname(__DIR__) . '/support_chat_widget.php'; ?>
<script>
    (() => {
        const nav = document.querySelector('.user-home-nav');
        if (!nav) return;
        const toggle = nav.querySelector('.user-home-nav-toggle');
        const links = nav.querySelector('.user-home-nav-links');
        toggle.addEventListener('click', () => {
            const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', String(!isExpanded));
            links.classList.toggle('is-open', !isExpanded);
        });
        const dropdownItems = [...nav.querySelectorAll('.user-home-dropdown')];
        nav.addEventListener('pointerover', (event) => {
            if (event.pointerType !== 'mouse' && event.pointerType !== 'pen') return;
            const hoveredItem = event.target.closest('.user-home-dropdown');
            if (!hoveredItem || !nav.contains(hoveredItem)) return;
            dropdownItems.forEach((item) => {
                item.querySelector('details').open = item === hoveredItem;
            });
        });
        nav.addEventListener('pointerleave', () => {
            if (nav.contains(document.activeElement)) return;
            dropdownItems.forEach((item) => {
                item.querySelector('details').open = false;
            });
        });
    })();
</script>
