<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';

$checkoutSuccessMessage = $_SESSION['checkout_success'] ?? null;
unset($_SESSION['checkout_success']);

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
    <link rel="stylesheet" href="<?= app_url('assets/css/user_home.css') ?>">
    <link rel="stylesheet" href="<?= app_url('assets/css/storefront_motion.css') ?>">
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

    <?php if (is_string($checkoutSuccessMessage) && $checkoutSuccessMessage !== ''): ?>
        <div class="modal fade" id="checkout-success-modal" tabindex="-1" aria-labelledby="checkout-success-title" aria-describedby="checkout-success-message" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 rounded-4 shadow">
                    <div class="modal-body text-center p-4 p-md-5">
                        <div class="mb-3 text-success" aria-hidden="true"><i class="bi bi-check-circle-fill" style="font-size:3rem"></i></div>
                        <h2 class="h4 fw-bold" id="checkout-success-title">Checkout complete</h2>
                        <p class="text-secondary mb-4" id="checkout-success-message"><?= htmlspecialchars($checkoutSuccessMessage, ENT_QUOTES, 'UTF-8') ?></p>
                        <button type="button" class="btn btn-dark rounded-pill px-4" data-bs-dismiss="modal">Continue shopping</button>
                    </div>
                </div>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modalElement = document.getElementById('checkout-success-modal');
                if (modalElement && window.bootstrap) bootstrap.Modal.getOrCreateInstance(modalElement).show();
            });
        </script>
    <?php endif; ?>

    <?php require dirname(__DIR__, 2) . '/components/home/dream_hero.php'; ?>

    <?php require dirname(__DIR__, 2) . '/components/home/timeless_furniture.php'; ?>

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
                        At furnara, we believe in the longevity of design. That's why we offer spare parts
                        for selected items to ensure your furniture remains functional and timeless. For assistance,
                        reach out to us at <b>customercare@furnaraform.com</b>.
                    </p>
                </div>
                <img src="<?= app_url('Pictures/wholegreensofa.jpg') ?>" class="img-fluid mt-3 rounded shadow-lg" alt="Modern Sofa with Green Wall">
            </div>
        </div>
    </section>


    <?php require dirname(__DIR__, 2) . '/components/home/comfort_style.php'; ?>

    <?php require dirname(__DIR__, 2) . '/components/home/customer_testimonials.php'; ?>

    <section class="home-about-section container" id="about-brand-story" aria-labelledby="about-brand-story-title">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <div class="home-about-collage" aria-label="Furniture and interior inspiration">
                    <img src="https://i.pinimg.com/236x/3a/cb/ef/3acbef179a84b1c951022bd01f9efde0.jpg" alt="Elegant furniture details" loading="lazy">
                    <img src="https://i.pinimg.com/236x/dc/20/f0/dc20f0762cdf7c40b9abfd9e1b947cab.jpg" alt="Modern living room" loading="lazy">
                    <img src="https://i.pinimg.com/236x/f0/5c/24/f05c24714857e8056a3aaa4ca107c6ab.jpg" alt="Dining room furniture" loading="lazy">
                    <img src="https://i.pinimg.com/236x/26/bd/42/26bd4237e5c33279ac4e702e302a8fc7.jpg" alt="Elegant bedroom" loading="lazy">
                </div>
            </div>
            <div class="col-lg-6 home-about-copy">
                <p class="catalog-eyebrow">About Furnara</p>
                <h2 id="about-brand-story-title">Timeless Elegance, Crafted for You</h2>
                <p class="mt-4">At <strong>Furnara</strong>, we believe that furniture is more than just decorâ€”it is a statement of style, quality, and comfort. With a keen eye for detail and a commitment to quality, we curate pieces that bring sophistication into your space.</p>
                <p>Every collection is designed with a blend of <strong>modern aesthetics</strong> and <strong>timeless craftsmanship</strong>, ensuring that your home tells a story of elegance and simplicity.</p>
                <a href="<?= app_url('pages/public/furniture.php?collection=all-living-space') ?>" class="home-about-cta">Explore our collection</a>
                <div class="mt-4">
                    <h3 class="h5 fw-semibold">Our Sustainable Vision</h3>
                    <p>We are committed to thoughtful choices that respect people and the planet, from considered materials to responsible craftsmanship.</p>
                    <h3 class="h5 fw-semibold mt-4">Spare Parts and Support</h3>
                    <p>We believe in the longevity of design. For help with spare parts for selected items, contact <a href="mailto:customercare@furnaraform.com">customercare@furnaraform.com</a>.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="home-designers-section" id="designer-directory" aria-labelledby="designer-directory-title">
        <div class="container">
            <p class="catalog-eyebrow text-center">The people behind the pieces</p>
            <h2 class="section-title" id="designer-directory-title">Meet Our Designers</h2>
            <div class="row g-4 justify-content-center">
                <?php
                $homeDesigners = [
                    ['Kevin Alcaide', 'Lead Furniture Architect', 'kevin.jpg', 'designerKevin'],
                    ['Hazel Felicilda', 'Modern Interiors Specialist', 'hazel.jpg', 'designerHazel'],
                    ['Mae Antonette Alterado', 'Sustainable Design Expert', 'alterado.png', 'designerMae'],
                    ['Sylver Fuentes', 'Woodcraft & Detailing Designer', 'Eber.jfif', 'designerSylver'],
                    ['Xyze Montalaba', 'Minimalist Space Planner', 'Mantalaba.png', 'designerXyze'],
                ];
                foreach ($homeDesigners as [$designerName, $designerRole, $designerImage, $designerModalId]):
                ?>
                    <div class="col-12 col-sm-6 col-lg-4">
                        <button class="user-designer-card w-100 border-0" type="button" data-bs-toggle="modal" data-bs-target="#<?= $designerModalId ?>">
                            <img src="<?= app_url('pictures/Designer/' . rawurlencode($designerImage)) ?>" alt="<?= htmlspecialchars($designerName, ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
                            <span class="user-designer-name d-block"><?= htmlspecialchars($designerName) ?></span>
                            <span class="user-designer-role d-block"><?= htmlspecialchars($designerRole) ?></span>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <?php foreach ($homeDesigners as [$designerName, $designerRole, $designerImage, $designerModalId]): ?>
        <div class="modal fade" id="<?= $designerModalId ?>" tabindex="-1" aria-labelledby="<?= $designerModalId ?>Label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content text-center p-3">
                    <img class="user-designer-modal-image" src="<?= app_url('pictures/Designer/' . rawurlencode($designerImage)) ?>" alt="<?= htmlspecialchars($designerName, ENT_QUOTES, 'UTF-8') ?>">
                    <h3 class="h5 mt-3" id="<?= $designerModalId ?>Label"><?= htmlspecialchars($designerName) ?></h3>
                    <p class="text-muted mb-0"><?= htmlspecialchars($designerRole) ?></p>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <?php require dirname(__DIR__, 2) . '/components/site_footer.php'; ?>




    <script src="<?= app_url('assets/js/storefront_motion.js') ?>" defer></script>
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
