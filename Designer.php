<?php
session_start();
include 'config.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Our Designers</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap">
    <link rel="stylesheet" href="Designer.css">
</head>

<body>

    <?php include('header.php'); ?>

    <div class="container py-5">
        <h2 class="section-title">Meet Our Designers</h2>
        <div class="row g-4 justify-content-center">
            <!-- Top designer -->
            <div class="col-12 col-md-6 col-lg-4">
                <div
                    class="designer-card"
                    data-bs-toggle="modal"
                    data-bs-target="#designer1Modal">
                    <img src="pictures/adcan dila boi.jpg" alt="Kevin Alcaide" />
                    <div class="designer-name">Kevin Alcaide</div>
                    <div class="designer-role">Lead Furniture Architect</div>
                </div>
            </div>


            <!-- Bottom designers in 2x2 -->
            <div class="col-12 col-md-6 col-lg-4">
                <div
                    class="designer-card"
                    data-bs-toggle="modal"
                    data-bs-target="#designer2Modal">
                    <img src="pictures/adcan dila boi.jpg" alt="Hazel Felicilda" />
                    <div class="designer-name">Hazel Felicilda</div>
                    <div class="designer-role">Modern Interiors Specialist</div>
                </div>
            </div>


            <div class="col-12 col-md-6 col-lg-4">
                <div
                    class="designer-card"
                    data-bs-toggle="modal"
                    data-bs-target="#designer3Modal">
                    <img
                        src="pictures/Designer/alterado.png"
                        alt="Mae Antonette Alterado" />
                    <div class="designer-name">Mae Antonette Alterado</div>
                    <div class="designer-role">Sustainable Design Expert</div>
                </div>
            </div>


            <div class="col-12 col-md-6 col-lg-4">
                <div
                    class="designer-card"
                    data-bs-toggle="modal"
                    data-bs-target="#designer4Modal">
                    <img src="pictures/Designer/Eber.jfif" alt="Sylver Fuentes" />
                    <div class="designer-name">Sylver Fuentes</div>
                    <div class="designer-role">Woodcraft & Detailing Designer</div>
                </div>
            </div>


            <div class="col-12 col-md-6 col-lg-4">
                <div
                    class="designer-card"
                    data-bs-toggle="modal"
                    data-bs-target="#designer5Modal">
                    <img src="pictures/Designer/Mantalaba.png" alt="Xyze Montalaba" />
                    <div class="designer-name">Xyze Montalaba</div>
                    <div class="designer-role">Minimalist Space Planner</div>
                </div>
            </div>
        </div>
    </div>


    <!-- Modals -->
    <div
        class="modal fade"
        id="designer1Modal"
        tabindex="-1"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center">
                <img
                    class="modal-img"
                    src="pictures/adcan dila boi.jpg"
                    alt="Kevin Alcaide" />
                <h5 class="mt-3">Kevin Alcaide</h5>
                <p class="text-muted">Lead Furniture Architect</p>
            </div>
        </div>
    </div>


    <div
        class="modal fade"
        id="designer2Modal"
        tabindex="-1"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center">
                <img
                    class="modal-img"
                    src="pictures/adcan dila boi.jpg"
                    alt="Hazel Felicilda" />
                <h5 class="mt-3">Hazel Felicilda</h5>
                <p class="text-muted">Modern Interiors Specialist</p>
            </div>
        </div>
    </div>


    <div
        class="modal fade"
        id="designer3Modal"
        tabindex="-1"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center">
                <img
                    class="modal-img"
                    src="pictures/Designer/alterado.png"
                    alt="Mae Antonette Alterado" />
                <h5 class="mt-3">Mae Antonette Alterado</h5>
                <p class="text-muted">Sustainable Design Expert</p>
            </div>
        </div>
    </div>


    <div
        class="modal fade"
        id="designer4Modal"
        tabindex="-1"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center">
                <img
                    class="modal-img"
                    src="pictures/Designer/Eber.jfif"
                    alt="Sylver Fuentes" />
                <h5 class="mt-3">Sylver Fuentes</h5>
                <p class="text-muted">Woodcraft & Detailing Designer</p>
            </div>
        </div>
    </div>


    <div
        class="modal fade"
        id="designer5Modal"
        tabindex="-1"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center">
                <img
                    class="modal-img"
                    src="pictures/Designer/Mantalaba.png"
                    alt="Xyze Montalaba" />
                <h5 class="mt-3">Xyze Montalaba</h5>
                <p class="text-muted">Minimalist Space Planner</p>
            </div>
        </div>
    </div>

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


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>