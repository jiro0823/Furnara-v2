<?php
require 'config.php'; // This already contains session_start() 
$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;

// Always use session_id (whether logged in or not) 
if (!isset($_SESSION['session_id'])) {
    $_SESSION['session_id'] = session_id();
}
$sessionId = $_SESSION['session_id'];

// Initialize cart array 
$cart = [];
if ($userId) {
    $query = "SELECT * FROM cart_items WHERE user_id = '$userId' AND status = 'active'";
} else {
    $query = "SELECT * FROM cart_items WHERE session_id = '$sessionId' AND status = 'active'";
}
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $cart[$row['furniture_id']] = [
            'quantity' => $row['quantity'],
            'name' => $row['name'],
            'price' => $row['price'],
            'image' => $row['image']
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Furniture</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="furniturestyle.css">
</head>

<body>
    <nav class="shadow-sm navbar navbar-expand-lg bg-light">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="pictures/furnara_header_logo.png" alt="Logo" height="40" id="logo">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="gap-5 mx-auto navbar-nav">
                        <li class="nav-item"><a class="nav-link active" href="user.php">Home</a></li>
                        <li class="nav-item"><a class="nav-link active" href="furniture.php">Furniture</a></li>
                        <li class="nav-item"><a class="nav-link" href="designer.php">Designer</a></li>
                        <li class="nav-item"><a class="nav-link" href="AboutUs.php">About Us</a></li>
                    </ul>
                    <div class="d-flex align-items-center gap-3">
                        <a href="search.php" class="text-dark fs-5">
                            <i class="bi bi-search"></i>
                        </a>
                        <a href="#" class="text-dark fs-5 position-relative" id="cart-icon">
                            <i class="bi bi-cart"></i>
                            <span id="cart-count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">0</span>
                        </a>
                        <a href="profile.php" class="text-dark fs-5">
                            <i class="bi bi-person-circle"></i>
                        </a>
                    </div>
                    <div class="d-flex ms-3">
                        <?php if ($isLoggedIn) { ?>
                            <a href="logout.php" class="btn btn-danger">Logout</a>
                        <?php } else { ?>
                            <a href="login.php" class="btn btn-outline-dark me-2">Log in</a>
                            <a href="register.php" class="btn btn-warning">Register</a>
                        <?php } ?>
                    </div>
                </div>
        </div>
    </nav>

    <!-- Add this section after the navigation bar -->
    <div class="hero-section container-fluid py-5">
        <div class="container">
            <div class="row align-items-center position-relative">
                <div class="col-lg-6">
                    <h1 class="display-4 mb-4">
                        <span class="fw-bold" id="f1">Find Your</span><br>
                        <span class="fw-normal" id="D1">Dream Furniture</span>
                    </h1>
                    <p class="lead mb-3" id="p1">
                        Explore World Class Top Furniture As Per Your Requirements & Choice
                    </p>
                </div>
                <div class="col-lg-6 position-absolute end-0 top-50 translate-middle-y">
                    <img src="pictures/bg-chair.png" alt="Chair" class="img-fluid chair-hero-image">
                </div>
            </div>
        </div>
    </div>

    <div class="section-background">
        <div class="furniture-section py-5">

            <!-- Category filters section -->
            <div class="container mt-5">
                <div class="category-filters">
                    <button class="category-btn fw-bold active" data-type="all">
                        <span>All</span>
                    </button>
                    <button class="category-btn fw-bold" data-type="bed">
                        <span>Beds</span>
                    </button>
                    <button class="category-btn fw-bold" data-type="sofa">
                        <span>Sofas</span>
                    </button>
                    <button class="category-btn fw-bold" data-type="table">
                        <span>Tables</span>
                    </button>
                    <button class="category-btn fw-bold" data-type="desk">
                        <span>Desks</span>
                    </button>
                    <button class="category-btn fw-bold" data-type="chair">
                        <span>Chairs</span>
                    </button>
                    <button class="category-btn fw-bold" data-type="cabinet">
                        <span>Cabinets</span>
                    </button>
                </div>
            </div>

            <div class="cart-container" id="cart">
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
                                <button class="btn btn-lg btn-outline-success payment-option" id="deliveryButton">
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
                                <div class="mb-3">
                                    <label for="deliveryAddress" class="form-label">Address</label>
                                    <textarea class="form-control" id="deliveryAddress" name="deliveryAddress" rows="3" required></textarea>
                                </div>
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


            <section class="container my-5">
                <div class="row" id="products-container">
                    <?php
                    // Pagination configuration
                    $products_per_page = 6;
                    $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                    $offset = ($current_page - 1) * $products_per_page;

                    // Get total products count
                    $total_query = "SELECT COUNT(*) as total FROM furniture WHERE stock > 0";
                    $total_result = mysqli_query($conn, $total_query);
                    $total_row = mysqli_fetch_assoc($total_result);
                    $total_products = $total_row['total'];
                    $total_pages = ceil($total_products / $products_per_page);

                    // Modified query with pagination
                    $query = "SELECT * FROM furniture WHERE stock > 0 LIMIT $products_per_page OFFSET $offset";
                    $result = mysqli_query($conn, $query);

                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            $imagePath = "uploads/" . htmlspecialchars($row['image']);
                            if (!file_exists($imagePath) || empty($row['image'])) {
                                $imagePath = "placeholder.png";
                            }

                            // Check if discount is active
                            $has_active_discount = false;
                            $current_date = date('Y-m-d');
                            if (
                                !is_null($row['discount_percentage']) &&
                                $row['discount_start'] <= $current_date &&
                                $row['discount_end'] >= $current_date
                            ) {
                                $has_active_discount = true;
                            }

                            // Display price with discount if applicable
                            $display_price = $has_active_discount ? $row['discounted_price'] : $row['price'];
                    ?>
                            <div class="col-md-4 mb-4 product-wrapper">
                                <div class="card h-100 shadow-sm product-card"
                                    data-category="<?php echo htmlspecialchars($row['category'] ?? 'all'); ?>"
                                    data-type="<?php echo htmlspecialchars($row['furniture_type'] ?? 'all'); ?>">
                                    <img src="<?php echo $imagePath; ?>" class="card-img-top product-img" alt="<?php echo htmlspecialchars($row['name']); ?>">
                                    <div class="card-body d-flex flex-column">
                                        <h5 class="card-title"><?php echo htmlspecialchars($row['name']); ?></h5>
                                        <div class="mt-auto">
                                            <?php if ($has_active_discount): ?>
                                                <div class="mb-2">
                                                    <span class="text-decoration-line-through text-muted">₱<?php echo number_format($row['price'], 2); ?></span>
                                                    <span class="badge bg-danger"><?php echo $row['discount_percentage']; ?>% OFF</span>
                                                </div>
                                                <h5 class="text-dark mb-3">₱<?php echo number_format($display_price, 2); ?></h5>
                                            <?php else: ?>
                                                <h5 class="text-dark mb-3">₱<?php echo number_format($display_price, 2); ?></h5>
                                            <?php endif; ?>
                                            <button class="btn btn-dark add-to-cart rounded-pill"
                                                data-id="<?php echo $row['furniture_id']; ?>"
                                                data-name="<?php echo htmlspecialchars($row['name']); ?>"
                                                data-price="<?php echo $display_price; ?>"
                                                data-stock="<?php echo $row['stock']; ?>"
                                                data-image="<?php echo $imagePath; ?>"
                                                <?php if (!$isLoggedIn) {
                                                    echo 'onclick="redirectToLogin(event)"';
                                                } ?>>
                                                Add to Cart <?php if ($row['stock'] <= 5) echo " (Only {$row['stock']} left)"; ?>
                                            </button>
                                            <button class="btn btn-outline-secondary view-details rounded-pill"
                                                data-id="<?php echo $row['furniture_id']; ?>"
                                                data-name="<?php echo htmlspecialchars($row['name']); ?>"
                                                data-price="<?php echo $display_price; ?>"
                                                data-original-price="<?php echo $row['price']; ?>"
                                                data-discount="<?php echo $has_active_discount ? $row['discount_percentage'] : '0'; ?>"
                                                data-description="<?php echo htmlspecialchars($row['description']); ?>"
                                                data-stock="<?php echo $row['stock']; ?>"
                                                data-image="<?php echo $imagePath; ?>">
                                                View Details
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                    <?php
                        }
                    } else {
                        echo '<div class="col-12 text-center"><p>No products available at the moment.</p></div>';
                    }
                    ?>
                </div>

                <!-- Add pagination navigation -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Product pagination" class="my-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($current_page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $current_page - 1; ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i === $current_page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($current_page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $current_page + 1; ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </section>

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


                document.getElementById("cart-icon").addEventListener("click", () => {
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
                        xhr.open("POST", "save_cart.php", true);
                        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xhr.send(
                            "furniture_id=" + id +
                            "&quantity=" + cart[id].quantity +
                            "&user_id=<?php echo $userId; ?>" +
                            "&session_id=<?php echo $sessionId; ?>" +
                            "&name=" + encodeURIComponent(cart[id].name) +
                            "&price=" + encodeURIComponent(cart[id].price) +
                            "&image=" + encodeURIComponent(cart[id].image)
                        );

                    });
                });

                document.querySelectorAll(".product-card").forEach(card => {
                    const viewDetailsBtn = card.querySelector('.view-details');

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
                    xhr.open("POST", "save_cart.php", true);
                    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                    xhr.send(
                        "furniture_id=" + id +
                        "&quantity=" + cart[id].quantity +
                        "&user_id=<?php echo $userId; ?>" +
                        "&session_id=<?php echo $sessionId; ?>" +
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
                        xhr.open("POST", "save_cart.php", true);
                        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xhr.send(
                            "furniture_id=" + id +
                            "&quantity=" + newQuantity +
                            "&user_id=<?php echo $userId; ?>" +
                            "&session_id=<?php echo $sessionId; ?>" +
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
                    xhr.open("POST", "remove_item.php", true);
                    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                    xhr.send("furniture_id=" + id + "&user_id=<?php echo $userId; ?>&session_id=<?php echo $sessionId; ?>");

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
                    window.location.href = "login.php"; // Redirects to the login page
                }

                function getCartTotal() {
                    let total = 0;
                    for (let id in cart) {
                        total += cart[id].price * cart[id].quantity;
                    }
                    return total;
                }

                // Modal handling functions
                function cleanupModal(modalElement) {
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if (modal) {
                        modal.hide();
                    }
                    // Remove any lingering backdrops
                    const backdrops = document.getElementsByClassName('modal-backdrop');
                    while (backdrops.length > 0) {
                        backdrops[0].remove();
                    }
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                }

                // Add event listeners for modal cleanup
                ['paymentModal', 'inStoreModal', 'deliveryModal', 'productModal'].forEach(modalId => {
                    const modalElement = document.getElementById(modalId);
                    modalElement.addEventListener('hidden.bs.modal', function() {
                        cleanupModal(this);
                    });
                });

                document.querySelectorAll('.btn-close, [data-bs-dismiss="modal"]').forEach(button => {
                    button.addEventListener('click', function() {
                        const modalElement = this.closest('.modal');
                        if (modalElement) {
                            cleanupModal(modalElement);
                        }
                    });
                });

                // Existing payment modal click handler
                document.querySelector('.cart-footer .btn').addEventListener('click', function() {
                    if (getCartTotal() === 0) {
                        alert("Your cart is empty!");
                        return;
                    }
                    cleanupModal(document.getElementById('paymentModal')); // Clean up first
                    const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
                    paymentModal.show();
                });

                function handleModalTransition(fromModalId, toModalId) {
                    const fromModal = document.getElementById(fromModalId);
                    const toModal = new bootstrap.Modal(document.getElementById(toModalId));

                    fromModal.addEventListener('hidden.bs.modal', function handler() {
                        toModal.show();
                        fromModal.removeEventListener('hidden.bs.modal', handler);
                    });

                    bootstrap.Modal.getInstance(fromModal).hide();
                }

                document.getElementById('inStoreButton').addEventListener('click', function() {
                    const paymentModal = document.getElementById('paymentModal');
                    const inStoreModal = document.getElementById('inStoreModal');
                    const backdrop = document.querySelector('.modal-backdrop');

                    if (backdrop) {
                        // Keep the backdrop and prevent it from being removed
                        backdrop.style.transition = 'none';
                        backdrop.style.opacity = '0.7';
                    }

                    bootstrap.Modal.getInstance(paymentModal).hide();
                    paymentModal.addEventListener('hidden.bs.modal', function handler() {
                        const newModal = new bootstrap.Modal(inStoreModal);
                        newModal.show();
                        paymentModal.removeEventListener('hidden.bs.modal', handler);
                    });
                });

                document.getElementById('deliveryButton').addEventListener('click', function() {
                    const paymentModal = document.getElementById('paymentModal');
                    const deliveryModal = document.getElementById('deliveryModal');
                    const backdrop = document.querySelector('.modal-backdrop');

                    if (backdrop) {
                        // Keep the backdrop and prevent it from being removed
                        backdrop.style.transition = 'none';
                        backdrop.style.opacity = '0.7';
                    }

                    bootstrap.Modal.getInstance(paymentModal).hide();
                    paymentModal.addEventListener('hidden.bs.modal', function handler() {
                        const newModal = new bootstrap.Modal(deliveryModal);
                        newModal.show();
                        paymentModal.removeEventListener('hidden.bs.modal', handler);
                    });
                });

                // Clean up backdrop only when explicitly closing modals
                document.querySelectorAll('.modal .btn-close, .modal').forEach(element => {
                    element.addEventListener('click', function(e) {
                        if (e.target === this || e.target.classList.contains('btn-close')) {
                            const modal = this.classList.contains('modal') ? this : this.closest('.modal');
                            const backdrop = document.querySelector('.modal-backdrop');

                            if (backdrop) {
                                backdrop.style.transition = 'opacity 0.3s ease';
                                backdrop.style.opacity = '0';
                                setTimeout(() => {
                                    backdrop.remove();
                                    document.body.classList.remove('modal-open');
                                    document.body.style.overflow = '';
                                    document.body.style.paddingRight = '';
                                }, 300);
                            }

                            bootstrap.Modal.getInstance(modal).hide();
                        }
                    });
                });

                document.getElementById('inStoreButton').addEventListener('click', function() {
                    handleModalTransition('paymentModal', 'inStoreModal');
                });

                document.getElementById('deliveryButton').addEventListener('click', function() {
                    handleModalTransition('paymentModal', 'deliveryModal');
                });

                document.getElementById('inStoreForm').addEventListener('submit', function(e) {
                    e.preventDefault();

                    const pickupDate = document.getElementById('pickupDate').value;
                    const pickupTime = document.getElementById('pickupTime').value;
                    const totalAmount = getCartTotal();

                    const bodyData = new URLSearchParams();
                    bodyData.append('pickupDate', pickupDate);
                    bodyData.append('pickupTime', pickupTime);
                    bodyData.append('totalAmount', totalAmount);

                    fetch('save_instore.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: bodyData.toString()
                        })
                        .then(response => response.text())
                        .then(data => {
                            alert(data);
                            bootstrap.Modal.getInstance(document.getElementById('inStoreModal')).hide();
                        })
                        .catch(error => {
                            alert('Error: ' + error);
                        });
                });

                document.getElementById('deliveryForm').addEventListener('submit', function(e) {
                    e.preventDefault();

                    const deliveryName = document.getElementById('deliveryName').value;
                    const deliveryAddress = document.getElementById('deliveryAddress').value;
                    const deliveryContact = document.getElementById('deliveryContact').value;
                    const deliveryDate = document.getElementById('deliveryDate')?.value || '';
                    const deliveryTime = document.getElementById('deliveryTime')?.value || '';
                    const totalAmount = getCartTotal();

                    const bodyData = new URLSearchParams();
                    bodyData.append('deliveryName', deliveryName);
                    bodyData.append('deliveryAddress', deliveryAddress);
                    bodyData.append('deliveryContact', deliveryContact);
                    bodyData.append('deliveryDate', deliveryDate);
                    bodyData.append('deliveryTime', deliveryTime);
                    bodyData.append('totalAmount', totalAmount);

                    fetch('save_delivery.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: bodyData.toString()
                        })
                        .then(response => response.text())
                        .then(data => {
                            alert(data);
                            bootstrap.Modal.getInstance(document.getElementById('deliveryModal')).hide();
                        })
                        .catch(error => {
                            alert('Error: ' + error);
                        });
                });


                // Update the filtering JavaScript code
                document.addEventListener('DOMContentLoaded', function() {
                    const categoryBtns = document.querySelectorAll('.category-btn');
                    let currentCategory = 'all';

                    function filterProducts() {
                        const products = document.querySelectorAll('.product-wrapper');
                        let visibleCount = 0;

                        products.forEach(product => {
                            const productCard = product.querySelector('.product-card');
                            const productType = productCard.dataset.type;

                            if (currentCategory === 'all' || productType === currentCategory) {
                                product.style.display = '';
                                visibleCount++;
                            } else {
                                product.style.display = 'none';
                            }
                        });

                        // If no products are visible, show a message
                        const noProductsMessage = document.getElementById('no-products-message');
                        if (visibleCount === 0) {
                            if (!noProductsMessage) {
                                const message = document.createElement('div');
                                message.id = 'no-products-message';
                                message.className = 'col-12 text-center mt-4';
                                message.innerHTML = '<p>No products available in this category.</p>';
                                document.getElementById('products-container').appendChild(message);
                            }
                        } else if (noProductsMessage) {
                            noProductsMessage.remove();
                        }
                    }

                    categoryBtns.forEach(btn => {
                        btn.addEventListener('click', function() {
                            categoryBtns.forEach(b => b.classList.remove('active'));
                            this.classList.add('active');
                            currentCategory = this.dataset.type;
                            filterProducts();
                        });
                    });

                    // Initial filter
                    filterProducts();
                });
            </script>

            <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>

</html>