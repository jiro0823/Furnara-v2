<?php
session_start();
include 'config.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Oswald:wght@200..700&family=Outfit:wght@100..900&family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Roboto+Serif:ital,opsz,wght@0,8..144,100..900;1,8..144,100..900&family=Sora:wght@100..800&display=swap');

        @import url('https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Oswald:wght@200..700&family=Outfit:wght@100..900&family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Sora:wght@100..800&display=swap');

        #p1 {
            font-family: Outfit, sans-serif;
            font-size: 1rem;
            line-height: 1.2;
            max-width: 80%;
        }

        #f1 {
            font-family: Outfit, sans-serif;
            line-height: 1.2;
            font-weight: .5rem;
        }
    </style>


</head>

<body>

    <?php include('header.php'); ?>

    <section class="container py-5">
        <div class="row align-items-center">
            <!-- Left: Image Collage -->
            <div class="col-md-6">
                <div class="d-flex flex-wrap gap-3">
                    <img src="https://i.pinimg.com/236x/3a/cb/ef/3acbef179a84b1c951022bd01f9efde0.jpg"
                        alt="Elegant Furniture" class="rounded shadow-lg img-fluid" style="width: 48%; max-width: 100%;">
                    <img src="https://i.pinimg.com/236x/dc/20/f0/dc20f0762cdf7c40b9abfd9e1b947cab.jpg"
                        alt="Modern Living Room" class="rounded shadow-lg img-fluid" style="width: 48%; max-width: 100%;">
                    <img src="https://i.pinimg.com/236x/f0/5c/24/f05c24714857e8056a3aaa4ca107c6ab.jpg"
                        alt="Luxury Dining" class="rounded shadow-lg img-fluid" style="width: 48%; max-width: 100%;">
                    <img src="https://i.pinimg.com/236x/26/bd/42/26bd4237e5c33279ac4e702e302a8fc7.jpg"
                        alt="Elegant Bedroom" class="rounded shadow-lg img-fluid" style="width: 48%; max-width: 100%;">
                </div>
            </div>

            <!-- Right: Content -->
            <div class="col-md-6">
                <h1 class="display-5 fw-bold text-dark" id="f1">Timeless Elegance, Crafted for You</h1>
                <p class="lead mt-3" id="p1">
                    At <strong>Elite Interiors</strong>, we believe that furniture is more than just decor—it’s a statement of style, luxury, and comfort.
                    With a keen eye for detail and a commitment to quality, we curate pieces that bring sophistication into your space.
                </p>
                <p class="lead mb-3" id="p1">
                    Every collection is designed with a blend of <strong>modern aesthetics</strong> and <strong>timeless craftsmanship</strong>, ensuring that your home tells a story of elegance and simplicity.
                </p>

                <a href="furniture.php" class="px-4 mt-3 btn btn-outline-dark">Explore Our Collection</a>
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

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
</body>

</html>