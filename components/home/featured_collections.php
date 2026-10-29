<section class="product-carousel-section py-5 bg-light" id="featured-collections" aria-labelledby="featured-collections-title">
        <div class="container">
            <h2 class="text-center fw-bold mb-5" id="featured-collections-title">Our Collection</h2>

            <div class="swiper productSwiper">
                <div class="swiper-wrapper">
                    <!-- Chairs -->
                    <div class="swiper-slide">
                        <div class="product-card">
                            <div class="product-image">
                                <img src="<?= app_url('pictures/bangko.png') ?>" alt="Chairs">
                            </div>
                            <div class="product-info">
                                <h5 class="product-title">Chairs</h5>
                                <p class="product-description">Elegant and comfortable seating solutions</p>
                                <a href="<?= app_url('pages/public/furniture.php?collection=all-living-space') ?>" class="btn btn-outline-dark">Explore</a>
                            </div>
                        </div>
                    </div>

                    <!-- Sofa -->
                    <div class="swiper-slide">
                        <div class="product-card">
                            <div class="product-image">
                                <img src="<?= app_url('Pictures/seatings.png') ?>" alt="Sofa">
                            </div>
                            <div class="product-info">
                                <h5 class="product-title">Sofa</h5>
                                <p class="product-description">Luxurious comfort for your living space</p>
                                <a href="<?= app_url('pages/public/furniture.php?collection=all-living-space') ?>" class="btn btn-outline-dark">Explore</a>
                            </div>
                        </div>
                    </div>

                    <!-- Tables -->
                    <div class="swiper-slide">
                        <div class="product-card">
                            <div class="product-image">
                                <img src="<?= app_url('Pictures/lamesaa.png') ?>" alt="Tables">
                            </div>
                            <div class="product-info">
                                <h5 class="product-title">Tables</h5>
                                <p class="product-description">Functional and stylish table designs</p>
                                <a href="<?= app_url('pages/public/furniture.php?collection=all-living-space') ?>" class="btn btn-outline-dark">Explore</a>
                            </div>
                        </div>
                    </div>

                    <!-- Cabinet -->
                    <div class="swiper-slide">
                        <div class="product-card">
                            <div class="product-image">
                                <img src="<?= app_url('pictures/cabinett.png') ?>" alt="Cabinet">
                            </div>
                            <div class="product-info">
                                <h5 class="product-title">Cabinet</h5>
                                <p class="product-description">Storage solutions with elegance</p>
                                <a href="<?= app_url('pages/public/furniture.php?collection=all-living-space') ?>" class="btn btn-outline-dark">Explore</a>
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