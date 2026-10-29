<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';

$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;

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
    <link rel="stylesheet" href="<?= app_url('assets/css/user_Style.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/account_drawer.css') ?>">

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
    <link rel="stylesheet" href="<?= app_url('assets/css/components/site_components.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/cart_drawer.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/support_chat_widget.css') ?>">
</head>

<body>

    <?php require dirname(__DIR__, 2) . '/components/navigation/user_home_navbar.php'; ?>

    <?php require dirname(__DIR__, 2) . '/components/home/dream_hero.php'; ?>

    <?php require dirname(__DIR__, 2) . '/components/home/timeless_furniture.php'; ?>

    

    <?php require dirname(__DIR__, 2) . '/components/home/brand_story.php'; ?>

    <?php require dirname(__DIR__, 2) . '/components/home/featured_collections.php'; ?>


    <section class="container py-5" id="sustainability-and-perspectives" aria-labelledby="perspectives-title">
        <!-- Title -->
        <h2 class="fw-bold text-center mb-5" id="perspectives-title">More perspectives</h2>

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
                <img src="<?= app_url('Pictures/likoranbulak.jpg') ?>" class="img-fluid mt-3 rounded shadow-lg" alt="Sofa">
            </div>

            <!-- Column 2 -->
            <div class="col-md-4">
                <img src="<?= app_url('Pictures/closeupnalingkuranan.jpg') ?>" class="img-fluid mb-3 rounded shadow-lg" alt="Elegant Chairs">
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
                <img src="<?= app_url('Pictures/wholegreensofa.jpg') ?>" class="img-fluid mt-3 rounded shadow-lg" alt="Modern Sofa with Green Wall">
            </div>
        </div>
    </section>


    <?php require dirname(__DIR__, 2) . '/components/home/comfort_style.php'; ?>

    <?php require dirname(__DIR__, 2) . '/components/home/customer_testimonials.php'; ?>




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
