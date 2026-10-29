<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';

$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$productId || $productId < 1) {
    header('Location: ' . app_url('pages/public/furniture.php'));
    exit;
}

$productStatement = mysqli_prepare($conn, 'SELECT * FROM furniture WHERE furniture_id = ? LIMIT 1');
mysqli_stmt_bind_param($productStatement, 'i', $productId);
mysqli_stmt_execute($productStatement);
$productResult = mysqli_stmt_get_result($productStatement);
$product = $productResult ? mysqli_fetch_assoc($productResult) : null;
mysqli_stmt_close($productStatement);

if (!$product) {
    http_response_code(404);
    $pageTitle = 'Product not found';
} else {
    $pageTitle = $product['name'];
}

$imageName = $product['image'] ?? '';
$imagePath = $imageName !== '' && is_file(APP_ROOT . '/uploads/' . $imageName)
    ? app_url('uploads/' . rawurlencode($imageName))
    : app_url('pictures/bg-chair.png');
$today = date('Y-m-d');
$hasActiveDiscount = $product && !empty($product['discount_percentage'])
    && (float)$product['discount_percentage'] > 0
    && !empty($product['discount_start']) && !empty($product['discount_end'])
    && $product['discount_start'] <= $today && $product['discount_end'] >= $today;
$displayPrice = $product ? ($hasActiveDiscount ? $product['discounted_price'] : $product['price']) : 0;
$userId = $_SESSION['user_id'] ?? null;
$currentCartQuantity = 0;
if ($product && $userId) {
    $cartQuantityStatement = mysqli_prepare($conn, "SELECT quantity FROM cart_items WHERE furniture_id = ? AND user_id = ? AND status = 'active' LIMIT 1");
    mysqli_stmt_bind_param($cartQuantityStatement, 'ii', $productId, $userId);
    mysqli_stmt_execute($cartQuantityStatement);
    $cartQuantityResult = mysqli_stmt_get_result($cartQuantityStatement);
    $currentCartQuantity = (int)(mysqli_fetch_assoc($cartQuantityResult)['quantity'] ?? 0);
    mysqli_stmt_close($cartQuantityStatement);
}
// furniture.stock is the unreserved quantity; active cart quantities have already been deducted.
$availableToAdd = $product ? max(0, (int)$product['stock']) : 0;
$productColors = $product ? array_values(array_filter(array_map('trim', explode(',', (string)($product['color'] ?? ''))))) : [];
$collectionDefinitions = [
    'all-living-space' => 'All Living Space', 'leather-sofas' => 'Leather Sofa', 'fabric-sofas' => 'Fabric Sofas',
    'accent-chairs' => 'Accent Chairs', 'center-tables' => 'Center Tables', 'side-tables' => 'Side Tables',
    'desks-console-tables' => 'Desks and Console Tables', 'tv-cabinets-storage' => 'TV Cabinets and Storage',
    'art-accessories' => 'Art and Accessories', 'all-dining' => 'All Dining', 'dining-sets' => 'Dining Sets',
    'dining-tables' => 'Dining Tables', 'dining-chairs' => 'Dining Chairs', 'sideboards' => 'Sideboards',
    'barstools-bar-tables' => 'Barstools and Bar Tables', 'beds' => 'Beds', 'bedroom-furniture' => 'Bedroom Furniture',
    'la-z-boy' => 'La-Z-Boy', 'office-desks' => 'Office Desks', 'office-chairs' => 'Office Chairs',
    'office-storage' => 'Office Storage', 'clearance' => 'Clearance',
];
$requestedCollection = is_string($_GET['collection'] ?? null) ? $_GET['collection'] : '';
$collectionTitle = $collectionDefinitions[$requestedCollection] ?? '';

$recommendationResult = false;
if ($product) {
    $recommendationStatement = mysqli_prepare($conn, 'SELECT * FROM furniture WHERE stock > 0 AND furniture_id <> ? ORDER BY RAND() LIMIT 4');
    mysqli_stmt_bind_param($recommendationStatement, 'i', $productId);
    mysqli_stmt_execute($recommendationStatement);
    $recommendationResult = mysqli_stmt_get_result($recommendationStatement);
}
$hasRecommendations = $recommendationResult && mysqli_num_rows($recommendationResult) > 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> | Furnara</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= app_url('assets/css/furniturestyle.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/catalog.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/user_home.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/product_detail.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/components/site_components.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/cart_drawer.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/support_chat_widget.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/account_drawer.css') ?>">
</head>
<body>
    <?php require dirname(__DIR__, 2) . '/components/navigation/user_home_navbar.php'; ?>

    <main class="product-detail-page">
        <?php if (!$product): ?>
            <section class="product-not-found">
                <p class="catalog-eyebrow">Furnara collection</p>
                <h1>We couldn't find that product.</h1>
                <a class="product-back-link" href="<?= app_url('pages/public/furniture.php') ?>">Return to furniture</a>
            </section>
        <?php else: ?>
            <?php if ($collectionTitle !== ''): ?>
                <section class="collection-sale-promo product-collection-intro" aria-labelledby="product-collection-title" data-scroll-reveal>
                    <p class="collection-sale-kicker">A little more room for good living</p>
                    <h1 id="product-collection-title"><?= htmlspecialchars($collectionTitle) ?> — Find a piece you’ll love</h1>
                    <p class="collection-sale-description">Bring warmth and character to your home with thoughtfully designed furniture made for everyday moments, then discover a piece you’ll love for years to come.</p>
                    <a href="<?= app_url('pages/public/furniture.php?' . http_build_query(['collection' => $requestedCollection])) ?>">Shop <?= htmlspecialchars($collectionTitle) ?></a>
                </section>
            <?php endif; ?>
            <nav class="product-breadcrumb" aria-label="Breadcrumb" data-scroll-reveal>
                <a href="<?= app_url('pages/public/furniture.php' . ($collectionTitle !== '' ? '?' . http_build_query(['collection' => $requestedCollection]) : '')) ?>"><?= $collectionTitle !== '' ? htmlspecialchars($collectionTitle) : 'Furniture' ?></a><span aria-hidden="true">/</span>
                <span><?= htmlspecialchars($product['furniture_type'] ?? 'Collection') ?></span><span aria-hidden="true">/</span>
                <span aria-current="page"><?= htmlspecialchars($product['name']) ?></span>
            </nav>

            <section class="product-detail-layout" aria-labelledby="product-title" data-scroll-reveal>
                <div class="product-detail-image-wrap">
                    <img src="<?= htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>" class="product-detail-image">
                </div>
                <div class="product-detail-information">
                    <p class="catalog-eyebrow"><?= htmlspecialchars($product['furniture_type'] ?? 'Furnara furniture') ?></p>
                    <h1 id="product-title"><?= htmlspecialchars($product['name']) ?></h1>
                    <div class="product-detail-price">
                        <?php if ($hasActiveDiscount): ?><span class="catalog-original-price">&#8369;<?= number_format((float)$product['price'], 2) ?></span><?php endif; ?>
                        <span>&#8369;<?= number_format((float)$displayPrice, 2) ?></span>
                    </div>
                    <p class="product-detail-stock <?= (int)$product['stock'] > 0 ? 'is-available' : 'is-unavailable' ?>">
                        <?= (int)$product['stock'] > 0 ? number_format((int)$product['stock']) . ' available' : 'Currently unavailable' ?>
                        <?php if ((int)$product['stock'] > 0 && (int)$product['stock'] <= 5): ?> · Low stock<?php endif; ?>
                    </p>
                    <div class="product-detail-description">
                        <?= nl2br(htmlspecialchars($product['description'] ?? 'Details for this piece are coming soon.', ENT_QUOTES, 'UTF-8')) ?>
                    </div>
                    <dl class="product-detail-specifications">
                        <div><dt>Product type</dt><dd><?= htmlspecialchars($product['furniture_type'] ?? 'Furniture') ?></dd></div>
                        <div><dt>Color</dt><dd><?= htmlspecialchars($product['color'] ?? 'Not specified') ?></dd></div>
                        <div><dt>Size</dt><dd><?= htmlspecialchars($product['size'] ?? 'Not specified') ?></dd></div>
                        <div><dt>Material</dt><dd><?= htmlspecialchars($product['material'] ?? 'Not specified') ?></dd></div>
                        <div><dt>Available stock</dt><dd><?= number_format((int)$product['stock']) ?> <?= (int)$product['stock'] === 1 ? 'unit' : 'units' ?></dd></div>
                        <div><dt>Availability</dt><dd><?= (int)$product['stock'] > 0 ? 'Available' : 'Out of stock' ?></dd></div>
                    </dl>
                    <?php if ($productColors): ?>
                        <div class="product-color-options" aria-label="Available colors">
                            <span class="product-color-label">Available colors</span>
                            <div class="product-color-list">
                                <?php foreach ($productColors as $color): ?><span class="product-color-chip"><?= htmlspecialchars($color) ?></span><?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="product-purchase-controls" data-max-quantity="<?= $availableToAdd ?>">
                        <div class="product-quantity-control" aria-label="Choose quantity">
                            <button type="button" class="quantity-stepper" data-quantity-change="-1" aria-label="Decrease quantity" <?= $availableToAdd < 1 ? 'disabled' : '' ?>>−</button>
                            <input id="product-quantity" type="number" value="1" min="1" max="<?= max(1, $availableToAdd) ?>" inputmode="numeric" aria-label="Quantity" <?= $availableToAdd < 1 ? 'disabled' : '' ?>>
                            <button type="button" class="quantity-stepper" data-quantity-change="1" aria-label="Increase quantity" <?= $availableToAdd < 1 ? 'disabled' : '' ?>>+</button>
                        </div>
                        <button id="product-add-to-cart" class="product-add-to-cart add-to-cart" data-id="<?= (int)$product['furniture_id'] ?>" type="button" <?= $availableToAdd < 1 ? 'disabled aria-disabled="true"' : '' ?>><?= (int)$product['stock'] < 1 ? 'Out of stock' : ($availableToAdd < 1 ? 'Stock limit reached' : 'Add to cart') ?></button>
                    </div>
                    <p id="product-cart-feedback" class="product-cart-feedback" role="status" aria-live="polite"></p>
                </div>
            </section>

            <section class="product-recommendations" aria-labelledby="recommendations-title" data-scroll-reveal>
                <div class="recommendations-heading">
                    <p class="catalog-eyebrow">Picked for you</p>
                    <div class="recommendations-heading-row"><h2 id="recommendations-title">You may also like</h2>
                    <?php if ($hasRecommendations): ?>
                        <div class="recommendation-controls" aria-label="Recommendation carousel controls">
                            <button type="button" data-recommendation-previous aria-label="Previous recommendations"><i class="bi bi-arrow-left"></i></button>
                            <button type="button" data-recommendation-toggle aria-label="Pause automatic recommendations"><i class="bi bi-pause-fill"></i><span>Pause</span></button>
                            <button type="button" data-recommendation-next aria-label="Next recommendations"><i class="bi bi-arrow-right"></i></button>
                        </div>
                    <?php endif; ?></div>
                </div>
                <?php if ($hasRecommendations): ?>
                <div class="recommendation-viewport" data-recommendation-viewport aria-label="Recommended products">
                    <div class="recommendations-grid" data-recommendation-track>
                        <?php while ($suggestion = mysqli_fetch_assoc($recommendationResult)):
                            $suggestionName = (string)($suggestion['name'] ?? 'Furniture');
                            $suggestionImageName = $suggestion['image'] ?? '';
                            $suggestionImage = $suggestionImageName !== '' && is_file(APP_ROOT . '/uploads/' . $suggestionImageName)
                                ? app_url('uploads/' . rawurlencode($suggestionImageName))
                                : app_url('pictures/bg-chair.png');
                        ?>
                            <a class="recommendation-product" href="<?= app_url('pages/public/product.php?' . http_build_query(array_filter(['id' => (int)$suggestion['furniture_id'], 'collection' => $requestedCollection ?: null]))) ?>">
                                <div class="catalog-product-image-wrap"><img class="catalog-product-image" src="<?= htmlspecialchars($suggestionImage, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($suggestionName, ENT_QUOTES, 'UTF-8') ?>" loading="lazy"></div>
                                <h3 class="catalog-product-name"><?= htmlspecialchars($suggestionName) ?></h3>
                                <span class="catalog-product-price">&#8369;<?= number_format((float)$suggestion['price'], 2) ?></span>
                            </a>
                        <?php endwhile; ?>
                    </div>
                </div>
                <?php else: ?>
                        <p class="catalog-no-results">More furniture recommendations will be available soon.</p>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
    <?php if ($product): ?>
        <script>
            (() => {
                const controls = document.querySelector('.product-purchase-controls');
                const quantityInput = document.getElementById('product-quantity');
                const addButton = document.getElementById('product-add-to-cart');
                const feedback = document.getElementById('product-cart-feedback');
                const maxQuantity = Number(controls.dataset.maxQuantity);
                const clampQuantity = (value) => Math.min(maxQuantity, Math.max(1, Number(value) || 1));

                controls.querySelectorAll('[data-quantity-change]').forEach((button) => {
                    button.addEventListener('click', () => {
                        quantityInput.value = clampQuantity(Number(quantityInput.value) + Number(button.dataset.quantityChange));
                    });
                });
                quantityInput.addEventListener('change', () => {
                    quantityInput.value = clampQuantity(quantityInput.value);
                });
                addButton.addEventListener('click', async () => {
                    addButton.disabled = true;
                    feedback.textContent = 'Adding to cart…';
                    const body = new URLSearchParams({
                        furniture_id: <?= (int)$product['furniture_id'] ?>,
                        quantity: <?= $currentCartQuantity ?> + clampQuantity(quantityInput.value),
                        name: <?= json_encode((string)$product['name']) ?>,
                        price: <?= json_encode((string)$displayPrice) ?>,
                        image: <?= json_encode($imageName) ?>
                    });

                    try {
                        const response = await fetch(<?= json_encode(app_url('api/cart/save_cart.php')) ?>, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body
                        });
                        if (!response.ok) throw new Error(await response.text());
                        feedback.textContent = 'Added to your cart.';
                    } catch (error) {
                        feedback.textContent = error.message || 'Could not add this item. Please try again.';
                    } finally {
                        addButton.disabled = maxQuantity < 1;
                    }
                });
            })();
        </script>
    <?php endif; ?>
    <?php require dirname(__DIR__, 2) . '/components/site_footer.php'; ?>
    <script src="<?= app_url('assets/js/product_page_motion.js') ?>" defer></script>
</body>
</html>
