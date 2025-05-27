<?php
require 'config.php'; // This already contains session_start()

$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;

// Always use session_id (whether logged in or not)
if (!isset($_SESSION['session_id'])) {
    $_SESSION['session_id'] = session_id();
}
$sessionId = $_SESSION['session_id'];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Furnera | Timeless Furniture</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap">
    <link rel="stylesheet" href="user_Style.css">

    <style>
        .testimonials-section {
            background-color: #fff;
        }

        .testimonial-card {
            background: #fff;
            border-radius: 15px;
            padding: 2.5rem;
            margin: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            text-align: center;
            position: relative;
        }

        .testimonial-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .testimonial-quote {
            position: absolute;
            top: 20px;
            left: 20px;
        }

        .testimonial-text {
            font-size: 1.1rem;
            line-height: 1.6;
            color: #555;
            margin: 1.5rem 0;
            font-style: italic;
        }

        .testimonial-author h5 {
            color: #333;
            font-weight: 600;
        }

        .testimonial-rating {
            font-size: 1.2rem;
        }

        .testimonialSwiper .swiper-pagination-bullet {
            width: 10px;
            height: 10px;
            background: #333;
            opacity: 0.3;
        }

        .testimonialSwiper .swiper-pagination-bullet-active {
            opacity: 1;
        }
    </style>


    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.css">
</head>

<body>

    <?php @include('header.php'); ?>

    <section>
        <main class="position-relative">
            <img src="Pictures/firstpic.jpg"
                alt="Furniture Design" class="img-fluid w-100 vh-100 object-fit-cover">

            <div class="position-absolute top-50 start-50 translate-middle" style="max-width: 550px; transform: translate(-55%, -50%);">
                <h1 class="custom-heading fw-bold">Timeless <br> Furniture, <br> Made to <br> Last</h1>
            </div>
        </main>
    </section>

    <!-- Sliding Cart -->
    <div id="cart-sidebar" class="cart-sidebar">
        <div class="cart-header">
            <h5>Your Cart</h5>
            <button id="close-cart" class="close-btn">&times;</button>
        </div>
        <div id="cart-content">
            <p class="text-center">Loading cart...</p>
        </div>
        <div class="cart-footer">
            <p>Total: <span id="cart-total">₱0.00</span></p>
            <a href="checkout.php" class="btn btn-success w-100">Checkout</a>
        </div>
    </div>

    <!-- Overlay when cart is open -->
    <div id="cart-overlay" class="cart-overlay"></div>

    <section class="container py-5">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
            <!-- Left Side - Heading -->
            <h2 class="fw-bold text-dark text-md-start text-center col-md-5">
                A Brand Born from <br> Passion for Design
            </h2>

            <!-- Right Side - Paragraphs -->
            <div class="col-md-6 text-muted text-md-start text-center">
                <h6>
                    At furnara, we believe in the power of simplicity and functionality, inspired by the
                    timeless elegance of Scandinavian design. Our furniture is crafted with the finest materials,
                    offering a blend of minimalism and comfort to elevate every space. With a focus on clean lines, natural textures, and practical design, we create pieces that not only look beautiful but also stand the test in time.
                </h6>
                <h6>
                    Rooted in the values of quality, sustainability, and innovation, Nordform brings the
                    essence of Nordic living to your home. Wheter you're furnishing a cozy apartment or a spacious living room, our carefylly curated collection will transform your space into a sanctuary of style and serinity
                </h6>
            </div>
        </div>
    </section>

    <section class="product-carousel-section py-5 bg-light">
        <div class="container">
            <h2 class="text-center fw-bold mb-5">Our Collection</h2>

            <div class="swiper productSwiper">
                <div class="swiper-wrapper">
                    <!-- Chairs -->
                    <div class="swiper-slide">
                        <div class="product-card">
                            <div class="product-image">
                                <img src="pictures/bangko.png" alt="Chairs">
                            </div>
                            <div class="product-info">
                                <h5 class="product-title">Chairs</h5>
                                <p class="product-description">Elegant and comfortable seating solutions</p>
                                <a href="furniture.php" class="btn btn-outline-dark">Explore</a>
                            </div>
                        </div>
                    </div>

                    <!-- Sofa -->
                    <div class="swiper-slide">
                        <div class="product-card">
                            <div class="product-image">
                                <img src="Pictures/seatings.png" alt="Sofa">
                            </div>
                            <div class="product-info">
                                <h5 class="product-title">Sofa</h5>
                                <p class="product-description">Luxurious comfort for your living space</p>
                                <a href="furniture.php" class="btn btn-outline-dark">Explore</a>
                            </div>
                        </div>
                    </div>

                    <!-- Tables -->
                    <div class="swiper-slide">
                        <div class="product-card">
                            <div class="product-image">
                                <img src="Pictures/lamesaa.png" alt="Tables">
                            </div>
                            <div class="product-info">
                                <h5 class="product-title">Tables</h5>
                                <p class="product-description">Functional and stylish table designs</p>
                                <a href="furniture.php" class="btn btn-outline-dark">Explore</a>
                            </div>
                        </div>
                    </div>

                    <!-- Cabinet -->
                    <div class="swiper-slide">
                        <div class="product-card">
                            <div class="product-image">
                                <img src="pictures/cabinett.png" alt="Cabinet">
                            </div>
                            <div class="product-info">
                                <h5 class="product-title">Cabinet</h5>
                                <p class="product-description">Storage solutions with elegance</p>
                                <a href="furniture.php" class="btn btn-outline-dark">Explore</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="swiper-pagination"></div>
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
            </div>
        </div>
    </section>


    <section class="container py-5">
        <!-- Title -->
        <h2 class="fw-bold text-center mb-5">More perspectives</h2>

        <div class="row g-4">
            <!-- Column 1 -->
            <div class="col-md-4">
                <div class="p-4 shadow-lg" style="background-color: #BFCAB5; border-radius: 15px;">
                    <h4 class="fw-bold">Our Sustainable Vision</h4>
                    <p>
                        At Nordform, we are committed to making thoughtful decisions that prioritize
                        the wellbeing of people and the planet. From sourcing eco-conscious materials
                        to ensuring responsible craftsmanship, sustainability is at the heart of everything we do.
                    </p>
                </div>
                <img src="Pictures/likoranbulak.jpg" class="img-fluid mt-3 rounded shadow-lg" alt="Sofa">
            </div>

            <!-- Column 2 -->
            <div class="col-md-4">
                <img src="Pictures/closeupnalingkuranan.jpg" class="img-fluid mb-3 rounded shadow-lg" alt="Elegant Chairs">
                <div class="p-4 shadow-lg" style="background-color: #F4E3B5; border-radius: 15px;">
                    <h4 class="fw-bold">Looking for Timeless Elegance?</h4>
                    <p>
                        Explore our full range of furniture, crafted to bring beauty and functionality to any space.
                    </p>
                </div>
            </div>

            <!-- Column 3 -->
            <div class="col-md-4">
                <div class="p-4 shadow-lg" style="background-color: #F2B6A0; border-radius: 15px;">
                    <h4 class="fw-bold">Spare Parts</h4>
                    <p>
                        At Nordform, we believe in the longevity of design. That's why we offer spare parts
                        for selected items to ensure your furniture remains functional and timeless. For assistance,
                        reach out to us at <b>customercare@nordform.com</b>.
                    </p>
                </div>
                <img src="Pictures/wholegreensofa.jpg" class="img-fluid mt-3 rounded shadow-lg" alt="Modern Sofa with Green Wall">
            </div>
        </div>
    </section>


    <section class="position-relative vh-100">
        <!-- Full-Screen Image -->
        <img src="Pictures/dining.jpg" alt="Furniture Design" class="img-fluid w-100 h-100 object-fit-cover">

        <!-- Adjusted Text Content -->
        <div class="position-absolute" style="top: 35%; left: 10%; transform: translate(0, -50%);">
            <h1 class="custom-heading">
                The Perfect<br>Balance of<br>Comfort and Style
            </h1>
        </div>
    </section>

    <section class="testimonials-section py-5">
        <div class="container">
            <h2 class="text-center fw-bold mb-5">What Our Customers Say</h2>

            <div class="swiper testimonialSwiper">
                <div class="swiper-wrapper">
                    <!-- Testimonial 1 -->
                    <div class="swiper-slide">
                        <div class="testimonial-card">
                            <div class="testimonial-quote">
                                <i class="bi bi-quote fs-1 text-muted opacity-25"></i>
                            </div>
                            <p class="testimonial-text">The quality and craftsmanship of Furnera's furniture is exceptional. Every piece I've purchased has exceeded my expectations.</p>
                            <div class="testimonial-rating mb-3">
                                <i class="bi bi-star-fill text-warning"></i>
                                <i class="bi bi-star-fill text-warning"></i>
                                <i class="bi bi-star-fill text-warning"></i>
                                <i class="bi bi-star-fill text-warning"></i>
                                <i class="bi bi-star-fill text-warning"></i>
                            </div>
                            <div class="testimonial-author">
                                <h5 class="mb-1">Sarah Johnson</h5>
                                <p class="text-muted small">Interior Designer</p>
                            </div>
                        </div>
                    </div>

                    <!-- Testimonial 2 -->
                    <div class="swiper-slide">
                        <div class="testimonial-card">
                            <div class="testimonial-quote">
                                <i class="bi bi-quote fs-1 text-muted opacity-25"></i>
                            </div>
                            <p class="testimonial-text">The modern designs combined with comfort make Furnera stand out. Their customer service is also outstanding!</p>
                            <div class="testimonial-rating mb-3">
                                <i class="bi bi-star-fill text-warning"></i>
                                <i class="bi bi-star-fill text-warning"></i>
                                <i class="bi bi-star-fill text-warning"></i>
                                <i class="bi bi-star-fill text-warning"></i>
                                <i class="bi bi-star-half text-warning"></i>
                            </div>
                            <div class="testimonial-author">
                                <h5 class="mb-1">Michael Chen</h5>
                                <p class="text-muted small">Home Owner</p>
                            </div>
                        </div>
                    </div>

                    <!-- Testimonial 3 -->
                    <div class="swiper-slide">
                        <div class="testimonial-card">
                            <div class="testimonial-quote">
                                <i class="bi bi-quote fs-1 text-muted opacity-25"></i>
                            </div>
                            <p class="testimonial-text">I love how each piece tells a story. The attention to detail and sustainable practices make me a loyal customer.</p>
                            <div class="testimonial-rating mb-3">
                                <i class="bi bi-star-fill text-warning"></i>
                                <i class="bi bi-star-fill text-warning"></i>
                                <i class="bi bi-star-fill text-warning"></i>
                                <i class="bi bi-star-fill text-warning"></i>
                                <i class="bi bi-star-fill text-warning"></i>
                            </div>
                            <div class="testimonial-author">
                                <h5 class="mb-1">Emma Rodriguez</h5>
                                <p class="text-muted small">Architect</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="swiper-pagination"></div>
            </div>
        </div>
    </section>


    <section class="bg-white text-dark py-5">
        <div class="container">
            <div class="row gy-4">
                <!-- Column 1 - Tagline & Social Icons -->
                <div class="col-md-4 text-md-start text-center">
                    <h4 class="fw-bold text-custom">Furnera</h4>

                    <p class="small text-secondary">Timeless Designs. Crafted for Comfort.</p>

                    <!-- Social Media Icons -->
                    <div class="d-flex gap-3 justify-content-md-start justify-content-center">
                        <a href="#" class="text-dark fs-5"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-dark fs-5"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="text-dark fs-5"><i class="bi bi-pinterest"></i></a>
                    </div>
                </div>

                <!-- Column 2 - Quick Links -->
                <div class="col-md-4 text-md-start text-center">
                    <h5 class="fw-bold text-custom">Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="furniture.php" class="text-decoration-none text-dark">Furniture</a></li>
                        <li><a href="designer.php" class="text-decoration-none text-dark">Designer</a></li>
                        <li><a href="about-us.php" class="text-decoration-none text-dark">About Us</a></li>
                    </ul>
                </div>

                <!-- Column 3 - Contact Info -->
                <div class="col-md-4 text-md-start text-center">
                    <h5 class="fw-bold text-custom">Contact Us</h5>
                    <p class="small text-secondary fw-medium" style="line-height: 1.8;">
                        <i class="bi bi-geo-alt-fill icon-custom"></i> 1234 Nordform Street, Oslo, Norway
                    </p>
                    <p class="small text-secondary fw-medium" style="line-height: 1.8;">
                        <i class="bi bi-telephone-fill icon-custom"></i> +47 123 456 789
                    </p>
                    <p class="small text-secondary fw-medium" style="line-height: 1.8;">
                        <i class="bi bi-envelope-fill icon-custom"></i> support@nordform.com
                    </p>
                </div>

            </div>
        </div>
    </section>

    <footer>
        <!-- Bottom Line -->
        <div class="text-center pt-4 border-top mt-4" style="background-color:rgb(39, 35, 34);">
            <p class="small text-light fw-medium mb-0">&copy; 2025 Furnera. All rights reserved.</p>
        </div>
    </footer>




    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        $(document).ready(function() {
            // Open cart
            $("#cart-icon").click(function(e) {
                e.preventDefault();
                $("#cart-sidebar").addClass("open");
                $("#cart-overlay").fadeIn();
                loadCart(); // Load cart data
            });

            // Close cart
            $("#close-cart, #cart-overlay").click(function() {
                $("#cart-sidebar").removeClass("open");
                $("#cart-overlay").fadeOut();
            });

            // Function to fetch cart data
            function loadCart() {
                $.ajax({
                    url: "fetch_cart.php", // Fetch cart data
                    method: "GET",
                    success: function(response) {
                        $("#cart-content").html(response);
                        updateCartTotal();
                    }
                });
            }

            // Function to update total price
            function updateCartTotal() {
                let total = 0;
                $(".cart-item-price").each(function() {
                    total += parseFloat($(this).text().replace("₱", ""));
                });
                $("#cart-total").text("₱" + total.toFixed(2));
            }
        });
    </script>

    <style>
        .cart-sidebar {
            position: fixed;
            right: -320px;
            top: 0;
            width: 300px;
            height: 100%;
            background: #fff;
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.2);
            transition: 0.3s ease-in-out;
            z-index: 1000;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }

        .cart-sidebar.open {
            right: 0;
        }

        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 18px;
            font-weight: bold;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
        }

        .cart-footer {
            margin-top: auto;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }

        .cart-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            z-index: 999;
        }
    </style>

    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        .product-carousel-section {
            background-color: #f8f9fa;
            overflow: hidden;
            padding: 4rem 0;
        }

        .product-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .product-image {
            height: 250px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .product-image img {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        .product-card:hover .product-image img {
            transform: scale(1.05);
        }

        .product-info {
            text-align: center;
        }

        .product-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .product-description {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }

        .swiper-button-next,
        .swiper-button-prev {
            color: #333;
            background: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .swiper-button-next:after,
        .swiper-button-prev:after {
            font-size: 18px;
        }

        .swiper-pagination-bullet {
            background: #333;
            opacity: 0.5;
        }

        .swiper-pagination-bullet-active {
            opacity: 1;
        }

        .btn-outline-dark {
            border-width: 2px;
            padding: 0.5rem 1.5rem;
            transition: all 0.3s ease;
        }

        .btn-outline-dark:hover {
            transform: translateY(-2px);
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.js"></script>
    <script>
        const swiper = new Swiper('.productSwiper', {
            slidesPerView: 1,
            spaceBetween: 30,
            loop: true,
            autoplay: {
                delay: 3000,
                disableOnInteraction: false,
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            breakpoints: {
                640: {
                    slidesPerView: 2,
                },
                1024: {
                    slidesPerView: 3,
                },
            },
            effect: 'slide',
            speed: 800,
            grabCursor: true,
        });
    </script>

    <script>
        // Initialize Testimonial Swiper
        const testimonialSwiper = new Swiper('.testimonialSwiper', {
            slidesPerView: 1,
            spaceBetween: 30,
            loop: true,
            autoplay: {
                delay: 5000,
                disableOnInteraction: false,
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            breakpoints: {
                768: {
                    slidesPerView: 2,
                },
                1024: {
                    slidesPerView: 3,
                },
            },
            effect: 'slide',
            speed: 800,
        });
    </script>

</body>

</html>