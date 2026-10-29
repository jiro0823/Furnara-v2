<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';
$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;
$hasSavedDeliveryPin = false;
if ($userId) {
    $locationStatement = $conn->prepare('SELECT delivery_address, delivery_latitude, delivery_longitude FROM users WHERE id = ? LIMIT 1');
    if ($locationStatement) {
        $locationStatement->bind_param('i', $userId);
        if ($locationStatement->execute()) {
            $savedLocation = $locationStatement->get_result()->fetch_assoc();
            $hasSavedDeliveryPin = $savedLocation
                && trim((string)($savedLocation['delivery_address'] ?? '')) !== ''
                && $savedLocation['delivery_latitude'] !== null
                && $savedLocation['delivery_longitude'] !== null;
        }
        $locationStatement->close();
    }
}

// Initialize cart array 
$cart = [];
if ($userId) {
    $cartStatement = mysqli_prepare($conn, "SELECT * FROM cart_items WHERE user_id = ? AND status = 'active'");
    mysqli_stmt_bind_param($cartStatement, 'i', $userId);
    mysqli_stmt_execute($cartStatement);
    $result = mysqli_stmt_get_result($cartStatement);
    if ($result && mysqli_num_rows($result) > 0) while ($row = mysqli_fetch_assoc($result)) {
        $cart[$row['furniture_id']] = [
            'quantity' => $row['quantity'],
            'name' => $row['name'],
            'price' => $row['price'],
            'image' => $row['image']
        ];
    }
    if ($cartStatement) mysqli_stmt_close($cartStatement);
}

$furnitureTypes = [];
$typeResult = mysqli_query($conn, "SELECT DISTINCT furniture_type FROM furniture WHERE furniture_type IS NOT NULL AND furniture_type <> '' AND stock > 0 ORDER BY furniture_type");
if ($typeResult) {
    while ($typeRow = mysqli_fetch_assoc($typeResult)) {
        $furnitureTypes[] = $typeRow['furniture_type'];
    }
}

$collectionDefinitions = [
    'all-living-space' => ['title' => 'All Living Space', 'types' => ['sofa', 'chair', 'table', 'desk', 'cabinet']],
    'leather-sofas' => ['title' => 'Leather Sofa', 'types' => ['sofa'], 'material' => 'leather'],
    'fabric-sofas' => ['title' => 'Fabric Sofas', 'types' => ['sofa'], 'material' => 'fabric'],
    'accent-chairs' => ['title' => 'Accent Chairs', 'types' => ['chair']],
    'center-tables' => ['title' => 'Center Tables', 'types' => ['table']],
    'side-tables' => ['title' => 'Side Tables', 'types' => ['table']],
    'desks-console-tables' => ['title' => 'Desks and Console Tables', 'types' => ['desk', 'table']],
    'tv-cabinets-storage' => ['title' => 'TV Cabinets and Storage', 'types' => ['cabinet']],
    'art-accessories' => ['title' => 'Art and Accessories', 'types' => ['art', 'accessories']],
    'all-dining' => ['title' => 'All Dining', 'types' => ['table', 'chair']],
    'dining-sets' => ['title' => 'Dining Sets', 'types' => ['table']],
    'dining-tables' => ['title' => 'Dining Tables', 'types' => ['table']],
    'dining-chairs' => ['title' => 'Dining Chairs', 'types' => ['chair']],
    'sideboards' => ['title' => 'Sideboards', 'types' => ['cabinet']],
    'barstools-bar-tables' => ['title' => 'Barstools and Bar Tables', 'types' => ['chair', 'table']],
    'beds' => ['title' => 'Beds', 'types' => ['bed']],
    'bedroom-furniture' => ['title' => 'Bedroom Furniture', 'types' => ['bed', 'cabinet']],
    'la-z-boy' => ['title' => 'La-Z-Boy', 'types' => ['sofa']],
    'office-desks' => ['title' => 'Office Desks', 'types' => ['desk']],
    'office-chairs' => ['title' => 'Office Chairs', 'types' => ['chair']],
    'office-storage' => ['title' => 'Office Storage', 'types' => ['cabinet']],
    'clearance' => ['title' => 'Clearance', 'types' => [], 'sale' => true],
];
$requestedCollection = is_string($_GET['collection'] ?? null) ? $_GET['collection'] : '';
$activeCollection = $collectionDefinitions[$requestedCollection] ?? null;
$collectionTitle = $activeCollection['title'] ?? 'Furniture';

$requestedTypes = $_GET['type'] ?? [];
if (!is_array($requestedTypes)) {
    $requestedTypes = [];
}
$selectedTypes = array_values(array_intersect($furnitureTypes, array_map('strval', $requestedTypes)));
$saleOnly = isset($_GET['sale']) && $_GET['sale'] === '1';
$sort = $_GET['sort'] ?? 'featured';
$allowedSorts = ['featured', 'latest', 'best-selling', 'price-low', 'price-high'];
if (!in_array($sort, $allowedSorts, true)) {
    $sort = 'featured';
}

$catalogConditions = ['f.stock > 0'];
if ($activeCollection && !empty($activeCollection['empty'])) {
    $catalogConditions[] = '1 = 0';
} elseif ($activeCollection && !empty($activeCollection['types'])) {
    $collectionTypes = array_map(static fn($type) => "'" . mysqli_real_escape_string($conn, $type) . "'", $activeCollection['types']);
    $catalogConditions[] = 'LOWER(f.furniture_type) IN (' . implode(', ', $collectionTypes) . ')';
}
if ($activeCollection && !empty($activeCollection['material'])) {
    $materialValue = mysqli_real_escape_string($conn, $activeCollection['material']);
    $matchingMaterialResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM furniture WHERE LOWER(furniture_type) = 'sofa' AND LOWER(material) LIKE '%$materialValue%'");
    $matchingMaterialCount = $matchingMaterialResult ? (int)(mysqli_fetch_assoc($matchingMaterialResult)['total'] ?? 0) : 0;
    if ($matchingMaterialCount > 0) {
        $catalogConditions[] = "LOWER(f.material) LIKE '%$materialValue%'";
    }
}
if ($activeCollection && !empty($activeCollection['sale'])) {
    $saleOnly = true;
}
if ($selectedTypes) {
    $escapedTypes = array_map(static fn($type) => "'" . mysqli_real_escape_string($conn, $type) . "'", $selectedTypes);
    $catalogConditions[] = 'LOWER(f.furniture_type) IN (' . implode(', ', $escapedTypes) . ')';
}
if ($saleOnly) {
    $catalogConditions[] = 'f.discount_percentage > 0 AND f.discount_start <= CURDATE() AND f.discount_end >= CURDATE()';
}
$catalogWhere = implode(' AND ', $catalogConditions);

$salesCountSql = "(SELECT COUNT(*) FROM orders o WHERE LOWER(o.status) IN ('accepted', 'delivered', 'completed') AND o.furniture_name LIKE CONCAT('%', f.name, '%'))";
$catalogOrder = match ($sort) {
    'latest' => 'f.furniture_id DESC',
    'best-selling' => $salesCountSql . ' DESC, f.furniture_id DESC',
    'price-low' => 'COALESCE(f.discounted_price, f.price) ASC',
    'price-high' => 'COALESCE(f.discounted_price, f.price) DESC',
    default => '(f.discount_percentage > 0 AND f.discount_start <= CURDATE() AND f.discount_end >= CURDATE()) DESC, f.furniture_id DESC',
};

$productsPerPage = 12;
$currentPage = max(1, (int)($_GET['page'] ?? 1));
$countResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM furniture f WHERE $catalogWhere");
$totalProducts = $countResult ? (int)(mysqli_fetch_assoc($countResult)['total'] ?? 0) : 0;
$totalPages = max(1, (int)ceil($totalProducts / $productsPerPage));
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $productsPerPage;
$productResult = mysqli_query($conn, "SELECT f.* FROM furniture f WHERE $catalogWhere ORDER BY $catalogOrder LIMIT $productsPerPage OFFSET $offset");

$paginationQuery = $_GET;
unset($paginationQuery['page']);
$paginationBase = app_url('pages/public/furniture.php') . '?' . http_build_query($paginationQuery);
$clearFiltersQuery = $activeCollection ? ['collection' => $requestedCollection] : [];
$clearFiltersUrl = app_url('pages/public/furniture.php') . ($clearFiltersQuery ? '?' . http_build_query($clearFiltersQuery) : '');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($collectionTitle) ?> | Furnara</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= app_url('assets/css/furniturestyle.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/catalog.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/user_home.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/components/site_components.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/cart_drawer.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/support_chat_widget.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/account_drawer.css') ?>">
</head>

<body>
    <?php require dirname(__DIR__, 2) . '/components/navigation/user_home_navbar.php'; ?>

    <main class="catalog-page" id="furniture-catalog">
        <?php if ($activeCollection): ?>
            <section class="collection-sale-promo" aria-labelledby="collection-sale-title">
                <p class="collection-sale-kicker">A little more room for good living</p>
                <h1 id="collection-sale-title">Dining Sale - Buy More, Save More</h1>
                <p class="collection-sale-description">Bring warmth and character to your home with thoughtfully designed furniture made for everyday moments, then discover a piece you’ll love for years to come.</p>
                <a href="<?= app_url('pages/public/furniture.php?' . http_build_query(['collection' => $requestedCollection, 'sale' => '1'])) ?>">Shop this collection</a>
            </section>
        <?php endif; ?>
        <div class="catalog-layout">
            <aside class="catalog-filter-sidebar" aria-label="Furniture filters">
                <form id="catalog-filter-form" method="get" action="<?= app_url('pages/public/furniture.php') ?>">
                    <?php if ($activeCollection): ?><input type="hidden" name="collection" value="<?= htmlspecialchars($requestedCollection, ENT_QUOTES, 'UTF-8') ?>"><?php endif; ?>
                    <div class="catalog-filter-heading">
                        <p>Refine your search</p>
                        <a href="<?= htmlspecialchars($clearFiltersUrl, ENT_QUOTES, 'UTF-8') ?>">Clear all</a>
                    </div>

                    <details class="catalog-filter-group" open>
                        <summary>Product type</summary>
                        <div class="catalog-filter-options">
                            <?php foreach ($furnitureTypes as $type): ?>
                                <label class="catalog-filter-option">
                                    <input type="checkbox" name="type[]" value="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>" <?= in_array($type, $selectedTypes, true) ? 'checked' : '' ?> onchange="this.form.requestSubmit()">
                                    <span><?= htmlspecialchars($type) ?></span>
                                </label>
                            <?php endforeach; ?>
                            <?php if (!$furnitureTypes): ?><p class="catalog-filter-empty">No product types available.</p><?php endif; ?>
                        </div>
                    </details>

                    <details class="catalog-filter-group" open>
                        <summary>Special selections</summary>
                        <div class="catalog-filter-options">
                            <label class="catalog-filter-option">
                                <input type="checkbox" name="sale" value="1" <?= $saleOnly ? 'checked' : '' ?> onchange="this.form.requestSubmit()">
                                <span>On sale</span>
                            </label>
                        </div>
                    </details>

                    <details class="catalog-filter-group" open>
                        <summary>Sort products</summary>
                        <div class="catalog-filter-options">
                            <?php foreach (['featured' => 'Featured', 'latest' => 'Latest designs', 'best-selling' => 'Best selling', 'price-low' => 'Price: low to high', 'price-high' => 'Price: high to low'] as $sortValue => $sortLabel): ?>
                                <label class="catalog-filter-option">
                                    <input type="radio" name="sort" value="<?= $sortValue ?>" <?= $sort === $sortValue ? 'checked' : '' ?> onchange="this.form.requestSubmit()">
                                    <span><?= $sortLabel ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </details>

                    <button class="catalog-apply-button" type="submit">Apply filters</button>
                </form>
            </aside>

            <div class="catalog-content">
                <div class="catalog-toolbar">
                    <div>
                        <p class="catalog-eyebrow">Furnara collection</p>
                         <h1><?= htmlspecialchars($collectionTitle) ?></h1>
                    </div>
                    <p class="catalog-result-count"><?= number_format($totalProducts) ?> <?= $totalProducts === 1 ? 'product' : 'products' ?></p>
                </div>

            <div class="cart-container" id="cart" hidden>
                <div class="cart-header">
                    <h5>Your Cart</h5>
                    <button class="btn-close" id="close-cart"></button>
                </div>
                <div class="cart-body" id="cart-items">
                    <p class="text-muted text-center" id="empty-cart-message">Cart is empty</p>
                </div>
                <div class="cart-footer">
                    <h5>Total: ₱<span id="cart-total">0.00</span></h5>
                    <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#paymentModal">Checkout</button>
                </div>
            </div>

            <!-- Payment Options Modal -->
            <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="paymentModalLabel">Choose Payment Method</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-center">
                            <p class="mb-4">How would you like to proceed with your order?</p>
                            <div class="d-grid gap-3">
                                <button class="btn btn-lg btn-outline-primary payment-option" id="inStoreButton">
                                    <i class="bi bi-shop me-2"></i>In-store Payment & Pickup
                                </button>
                                <button class="btn btn-lg btn-outline-success payment-option" id="deliveryButton" data-has-delivery-pin="<?= $hasSavedDeliveryPin ? '1' : '0' ?>">
                                    <i class="bi bi-truck me-2"></i>Delivery
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- In-Store Booking Modal -->
            <div class="modal fade" id="inStoreModal" tabindex="-1" aria-labelledby="inStoreModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <form id="inStoreForm">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="inStoreModalLabel">Book In-Store Pickup</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="pickupDate" class="form-label">Choose Date</label>
                                    <input type="date" class="form-control" id="pickupDate" name="pickupDate" required>
                                </div>
                                <div class="mb-3">
                                    <label for="pickupTime" class="form-label">Choose Time</label>
                                    <input type="time" class="form-control" id="pickupTime" name="pickupTime" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary rounded-pill">Confirm Pickup</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Delivery Modal -->
            <div class="modal fade" id="deliveryModal" tabindex="-1" aria-labelledby="deliveryModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <form id="deliveryForm">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="deliveryModalLabel">Enter Delivery Details</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="deliveryName" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="deliveryName" name="deliveryName" required>
                                </div>
                                <p class="mb-3">Your delivery address and location come from the map pin saved in your <a href="<?= app_url('pages/account/profile.php') ?>">profile</a>.</p>
                                <div class="mb-3">
                                    <label for="deliveryContact" class="form-label">Contact Number</label>
                                    <input type="text" class="form-control" id="deliveryContact" name="deliveryContact" required>
                                </div>
                                <div class="mb-3">
                                    <label for="deliveryDate" class="form-label">Delivery Date</label>
                                    <input type="date" class="form-control" id="deliveryDate" name="deliveryDate" required>
                                </div>
                                <div class="mb-3">
                                    <label for="deliveryTime" class="form-label">Delivery Time</label>
                                    <input type="time" class="form-control" id="deliveryTime" name="deliveryTime" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-success rounded-pill">Confirm Delivery</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>


            <!-- Product Detail Modal -->
            <div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="productModalLabel">Product Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <img src="" id="modalProductImage" class="img-fluid rounded" alt="Product Image">
                                </div>
                                <div class="col-md-6">
                                    <h3 id="modalProductName"></h3>
                                    <div id="modalPriceSection">
                                        <!-- Price will be populated dynamically -->
                                    </div>
                                    <div class="mb-3">
                                        <h5>Description:</h5>
                                        <p id="modalProductDescription"></p>
                                    </div>
                                    <div class="mb-3">
                                        <h5>Stock Status:</h5>
                                        <p id="modalStockStatus"></p>
                                    </div>
                                    <div class="d-grid">
                                        <button class="btn btn-primary add-to-cart-modal"
                                            data-id=""
                                            data-name=""
                                            data-price=""
                                            data-stock=""
                                            data-image=""
                                            <?php if (!$isLoggedIn) {
                                                echo 'onclick="redirectToLogin(event)"';
                                            } ?>>
                                            Add to Cart
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


                <section class="catalog-products-section" id="furniture-product-results" aria-label="Furniture products">
                    <div class="catalog-products-grid" id="products-container">
                        <?php if ($productResult && mysqli_num_rows($productResult) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($productResult)):
                                $imageName = $row['image'] ?? '';
                                $imageFilePath = APP_ROOT . '/uploads/' . $imageName;
                                $imagePath = !empty($imageName) && is_file($imageFilePath)
                                    ? app_url('uploads/' . rawurlencode($imageName))
                                    : app_url('pictures/bg-chair.png');
                                $today = date('Y-m-d');
                                $hasActiveDiscount = !empty($row['discount_percentage'])
                                    && (float)$row['discount_percentage'] > 0
                                    && !empty($row['discount_start']) && !empty($row['discount_end'])
                                    && $row['discount_start'] <= $today && $row['discount_end'] >= $today;
                                $displayPrice = $hasActiveDiscount ? $row['discounted_price'] : $row['price'];
                                $name = (string)($row['name'] ?? 'Furniture');
                            ?>
                                <article class="catalog-product">
                                    <a class="catalog-product-link" href="<?= app_url('pages/public/product.php?' . http_build_query(array_filter(['id' => (int)$row['furniture_id'], 'collection' => $requestedCollection ?: null]))) ?>" aria-label="View <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>">
                                        <div class="catalog-product-image-wrap">
                                            <img class="catalog-product-image" src="<?= htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
                                            <?php if ($hasActiveDiscount): ?><span class="catalog-sale-label"><?= (int)$row['discount_percentage'] ?>% off</span><?php endif; ?>
                                        </div>
                                        <div class="catalog-product-info">
                                            <h2 class="catalog-product-name"><?= htmlspecialchars($name) ?></h2>
                                            <p class="catalog-product-specs"><?= htmlspecialchars(ucfirst((string)($row['furniture_type'] ?? 'Furniture'))) ?><?php if (!empty($row['color'])): ?> <span aria-hidden="true">·</span> <?= htmlspecialchars((string)$row['color']) ?><?php endif; ?></p>
                                            <div class="catalog-product-prices">
                                                <?php if ($hasActiveDiscount): ?><span class="catalog-original-price">&#8369;<?= number_format((float)$row['price'], 2) ?></span><?php endif; ?>
                                                <span class="catalog-product-price">&#8369;<?= number_format((float)$displayPrice, 2) ?></span>
                                            </div>
                                        </div>
                                    </a>
                                    <button class="catalog-quick-view view-details" type="button" data-id="<?= (int)$row['furniture_id'] ?>" data-name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" data-price="<?= htmlspecialchars((string)$displayPrice, ENT_QUOTES, 'UTF-8') ?>" data-original-price="<?= htmlspecialchars((string)$row['price'], ENT_QUOTES, 'UTF-8') ?>" data-discount="<?= $hasActiveDiscount ? (int)$row['discount_percentage'] : 0 ?>" data-description="<?= htmlspecialchars($row['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>" data-stock="<?= (int)$row['stock'] ?>" data-image="<?= htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8') ?>">Quick view</button>
                                </article>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="catalog-no-results">No furniture matches these filters. Try clearing a filter.</p>
                        <?php endif; ?>
                    </div>
                    <?php if ($totalPages > 1): ?>
                        <nav id="furniture-pagination" class="catalog-pagination" aria-label="Product pagination">
                            <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?>
                                <?php $pageUrl = $paginationBase . '&page=' . $pageNumber; ?>
                                <a class="<?= $pageNumber === $currentPage ? 'is-current' : '' ?>" href="<?= htmlspecialchars($pageUrl, ENT_QUOTES, 'UTF-8') ?>" <?= $pageNumber === $currentPage ? 'aria-current="page"' : '' ?>><?= $pageNumber ?></a>
                            <?php endfor; ?>
                        </nav>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </main>

    <?php require dirname(__DIR__, 2) . '/components/site_footer.php'; ?>

            <script>
                const cartItems = document.getElementById("cart-items");
                const cartTotal = document.getElementById("cart-total");
                const cartCount = document.getElementById("cart-count");
                const emptyCartMessage = document.getElementById("empty-cart-message");
                let totalAmount = 0;
                let cart = <?php echo json_encode($cart); ?>; // Load cart from PHP

                for (let id in cart) {
                    cart[id].quantity = parseInt(cart[id].quantity) || 0;
                }
                updateCartUI();


                document.getElementById("cart-icon").addEventListener("click", (event) => {
                    event.preventDefault();
                    document.getElementById("cart").classList.add("show");
                });

                document.getElementById("close-cart").addEventListener("click", () => {
                    document.getElementById("cart").classList.remove("show");
                });

                document.querySelectorAll(".add-to-cart").forEach(button => {
                    button.addEventListener("click", function() {
                        const id = this.dataset.id;
                        const name = this.dataset.name;
                        const price = parseFloat(this.dataset.price);
                        const image = this.dataset.image;

                        if (!cart[id]) {
                            cart[id] = {
                                name,
                                price,
                                image,
                                quantity: 1
                            };
                        } else {
                            cart[id].quantity = parseInt(cart[id].quantity) + 1;
                        }
                        updateCartUI();


                        const xhr = new XMLHttpRequest();
                        xhr.open("POST", <?= json_encode(app_url('api/cart/save_cart.php')) ?>, true);
                        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xhr.send(
                            "furniture_id=" + id +
                            "&quantity=" + cart[id].quantity +
                            "&user_id=<?php echo $userId; ?>" +
                            "" +
                            "&name=" + encodeURIComponent(cart[id].name) +
                            "&price=" + encodeURIComponent(cart[id].price) +
                            "&image=" + encodeURIComponent(cart[id].image)
                        );

                    });
                });

                document.querySelectorAll(".view-details").forEach(viewDetailsBtn => {
                    viewDetailsBtn.addEventListener("click", function() {
                        const product = this.dataset;

                        // Set modal content
                        document.getElementById("modalProductName").textContent = product.name;

                        // Handle price display with discounts
                        const modalPriceSection = document.getElementById("modalPriceSection");
                        if (parseFloat(product.discount) > 0) {
                            modalPriceSection.innerHTML = `
                        <div class="mb-2">
                            <span class="text-decoration-line-through text-muted">₱${parseFloat(product.originalPrice).toFixed(2)}</span>
                            <span class="badge bg-danger">${product.discount}% OFF</span>
                        </div>
                        <h5 class="text-primary">₱${parseFloat(product.price).toFixed(2)}</h5>
                    `;
                        } else {
                            modalPriceSection.innerHTML = `<h5 class="text-dark">₱${parseFloat(product.price).toFixed(2)}</h5>`;
                        }

                        document.getElementById("modalProductImage").src = product.image;
                        document.getElementById("modalProductDescription").textContent = product.description;

                        // Update stock status
                        const stock = parseInt(product.stock);
                        const stockStatus = document.getElementById("modalStockStatus");
                        if (stock <= 0) {
                            stockStatus.innerHTML = '<span class="text-danger">Out of Stock</span>';
                        } else if (stock <= 5) {
                            stockStatus.innerHTML = `<span class="text-warning">Only ${stock} items left!</span>`;
                        } else {
                            stockStatus.innerHTML = '<span class="text-success">In Stock</span>';
                        }

                        // Update add to cart button
                        const modalAddToCartBtn = document.querySelector(".add-to-cart-modal");
                        modalAddToCartBtn.dataset.id = product.id;
                        modalAddToCartBtn.dataset.name = product.name;
                        modalAddToCartBtn.dataset.price = product.price;
                        modalAddToCartBtn.dataset.stock = product.stock;
                        modalAddToCartBtn.dataset.image = product.image;

                        if (stock <= 0) {
                            modalAddToCartBtn.disabled = true;
                            modalAddToCartBtn.textContent = "Out of Stock";
                            modalAddToCartBtn.classList.remove("btn-dark");
                            modalAddToCartBtn.classList.add("btn-secondary");
                        } else {
                            modalAddToCartBtn.disabled = false;
                            modalAddToCartBtn.textContent = "Add to Cart";
                            modalAddToCartBtn.classList.remove("btn-secondary");
                            modalAddToCartBtn.classList.add("btn-dark");
                        }

                        // Show the modal
                        const productModal = new bootstrap.Modal(document.getElementById('productModal'));
                        productModal.show();
                    });
                });

                // Add to cart from modal with stock validation
                document.querySelector(".add-to-cart-modal").addEventListener("click", function() {
                    const id = this.dataset.id;
                    const name = this.dataset.name;
                    const price = parseFloat(this.dataset.price);
                    const stock = parseInt(this.dataset.stock);
                    const image = this.dataset.image;

                    // Check if adding would exceed stock
                    const currentQuantity = cart[id] ? cart[id].quantity : 0;
                    if (currentQuantity + 1 > stock) {
                        alert("Cannot add more items than available in stock!");
                        return;
                    }

                    if (!cart[id]) {
                        cart[id] = {
                            name,
                            price,
                            image,
                            quantity: 1
                        };
                    } else {
                        cart[id].quantity = parseInt(cart[id].quantity) + 1;
                    }
                    updateCartUI();

                    // Save the cart to the database
                    const xhr = new XMLHttpRequest();
                    xhr.open("POST", <?= json_encode(app_url('api/cart/save_cart.php')) ?>, true);
                    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                    xhr.send(
                        "furniture_id=" + id +
                        "&quantity=" + cart[id].quantity +
                        "&user_id=<?php echo $userId; ?>" +
                        "" +
                        "&name=" + encodeURIComponent(cart[id].name) +
                        "&price=" + encodeURIComponent(cart[id].price) +
                        "&image=" + encodeURIComponent(cart[id].image)
                    );

                    // Close modal after adding to cart
                    bootstrap.Modal.getInstance(document.getElementById('productModal')).hide();
                });

                function updateCartUI() {
                    cartItems.innerHTML = "";
                    totalAmount = 0;
                    let count = 0;

                    for (let id in cart) {
                        let item = cart[id];
                        totalAmount += item.price * item.quantity; // Ensure using original price
                        count += item.quantity;

                        cartItems.innerHTML +=
                            `<div class="cart-item">
                        <img src="${item.image}" alt="${item.name}">
                        <div class="cart-item-details">
                            <p class="mb-0">${item.name} x${item.quantity} - ₱${(item.price * item.quantity).toFixed(2)}</p>
                        </div>
                        <div class="cart-item-actions">
                            <button class="btn btn-sm btn-dark" onclick="updateQuantity('${id}', -1)">-</button>
                            <span class="mx-2">${item.quantity}</span>
                            <button class="btn btn-sm btn-dark" onclick="updateQuantity('${id}', 1)">+</button>
                            <button class="btn btn-danger btn-sm" onclick="removeItem('${id}')">X</button>
                        </div>
                    </div>`; // Update cart item display
                    }

                    cartTotal.innerText = totalAmount.toFixed(2);
                    cartCount.innerText = count;
                    emptyCartMessage.style.display = count ? "none" : "block";
                }

                function updateQuantity(id, change) {
                    if (cart[id]) {
                        const newQuantity = cart[id].quantity + change;

                        const xhr = new XMLHttpRequest();
                        xhr.open("POST", <?= json_encode(app_url('api/cart/save_cart.php')) ?>, true);
                        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xhr.send(
                            "furniture_id=" + id +
                            "&quantity=" + newQuantity +
                            "&user_id=<?php echo $userId; ?>" +
                            "" +
                            "&name=" + encodeURIComponent(cart[id].name) +
                            "&price=" + encodeURIComponent(cart[id].price) +
                            "&image=" + encodeURIComponent(cart[id].image)
                        );

                        xhr.onload = function() {
                            if (xhr.status === 200) {
                                cart[id].quantity = newQuantity;
                                if (cart[id].quantity <= 0) {
                                    delete cart[id];
                                }
                                updateCartUI();
                            } else {
                                alert(xhr.responseText);
                            }
                        };
                    }
                }

                function removeItem(id) {
                    // Remove from UI first
                    delete cart[id];
                    updateCartUI();

                    // Send request to remove the item from the database
                    const xhr = new XMLHttpRequest();
                    xhr.open("POST", <?= json_encode(app_url('api/cart/remove_item.php')) ?>, true);
                    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                    xhr.send("furniture_id=" + id + "&user_id=<?php echo $userId; ?>");

                    xhr.onload = function() {
                        if (xhr.status === 200) {
                            console.log('Item removed from the database');
                        } else {
                            console.log('Error removing item from the database');
                        }
                    };
                }


                function redirectToLogin(event) {
                    event.preventDefault();
                    window.location.href = <?= json_encode(app_url('pages/auth/login.php')) ?>; // Redirects to the login page
                }

                async function getCartTotal() {
                    const endpoint = document.getElementById('furnara-cart-config')?.dataset.endpoint;
                    if (!endpoint) throw new Error('Cart service is unavailable. Refresh and try again.');
                    const response = await fetch(endpoint, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
                    const data = await response.json();
                    if (!response.ok || !data.success) throw new Error(data.message || 'Your cart could not be checked.');
                    if (!Array.isArray(data.items) || data.items.length === 0 || Number(data.total) <= 0) {
                        throw new Error('Your cart is empty. Add a product before checkout.');
                    }
                    return Number(data.total);
                }

                document.querySelector('.cart-footer .btn')?.addEventListener('click', async function() {
                    try {
                        await getCartTotal();
                        bootstrap.Modal.getOrCreateInstance(document.getElementById('paymentModal')).show();
                    } catch (error) {
                        alert(error.message);
                    }
                });

                async function transitionCheckoutModal(targetId) {
                    const currentModal = document.getElementById('paymentModal');
                    const targetModal = document.getElementById(targetId);
                    currentModal.addEventListener('hidden.bs.modal', function handler() {
                        bootstrap.Modal.getOrCreateInstance(targetModal).show();
                        currentModal.removeEventListener('hidden.bs.modal', handler);
                    }, { once: true });
                    bootstrap.Modal.getOrCreateInstance(currentModal).hide();
                }
                document.getElementById('inStoreButton')?.addEventListener('click', () => transitionCheckoutModal('inStoreModal'));
                document.getElementById('deliveryButton')?.addEventListener('click', function() {
                    if (this.dataset.hasDeliveryPin !== '1') {
                        alert('Please pin and save your delivery location in your profile before checkout.');
                        window.location.href = <?= json_encode(app_url('pages/account/profile.php')) ?>;
                        return;
                    }
                    transitionCheckoutModal('deliveryModal');
                });

                async function submitCheckout(form, orderType) {
                    const submitButton = form.querySelector('[type="submit"]');
                    const originalLabel = submitButton.textContent;
                    submitButton.disabled = true;
                    submitButton.textContent = 'Preparing checkout...';
                    try {
                        const bodyData = new URLSearchParams(new FormData(form));
                        bodyData.set('order_type', orderType);
                        bodyData.set('csrf_token', document.getElementById('furnara-cart-config')?.dataset.csrfToken || '');
                        const response = await fetch(<?= json_encode(app_url('api/orders/prepare_checkout.php')) ?>, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
                            body: bodyData.toString(),
                            credentials: 'same-origin'
                        });
                        const result = await response.json();
                        if (!response.ok || !result.success) throw new Error(result.message || 'Checkout could not be prepared. Please try again.');
                        window.location.assign(result.redirect);
                    } catch (error) {
                        alert(error.message || 'Checkout failed. Please try again.');
                    } finally {
                        submitButton.disabled = false;
                        submitButton.textContent = originalLabel;
                    }
                }

                document.getElementById('inStoreForm')?.addEventListener('submit', function(event) {
                    event.preventDefault();
                    submitCheckout(this, 'instore');
                });
                document.getElementById('deliveryForm')?.addEventListener('submit', function(event) {
                    event.preventDefault();
                    submitCheckout(this, 'delivery');
                });


                // Product rows fade into view and gently fade out as they leave the viewport.
                document.documentElement.classList.add('has-scroll-reveal');
                const revealObserver = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        entry.target.classList.toggle('is-visible', entry.isIntersecting);
                    });
                }, { threshold: 0.08, rootMargin: '0px 0px -6% 0px' });
                document.querySelectorAll('.catalog-product').forEach((product) => revealObserver.observe(product));
            </script>

            <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
            <?php if (!empty($_GET['checkout']) && $isLoggedIn): ?>
                <script>window.addEventListener('load',()=>{const modal=document.getElementById('paymentModal');if(modal&&window.bootstrap)bootstrap.Modal.getOrCreateInstance(modal).show();});</script>
            <?php endif; ?>
</body>

</html>
