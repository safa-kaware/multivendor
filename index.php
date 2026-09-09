<?php

require_once "config/app.php";

$pageTitle = "Home | " . APP_NAME;

$metaDescription =
    "Discover quality products from multiple trusted vendors.";


/*
|--------------------------------------------------------------------------
| Get Active Banners
|--------------------------------------------------------------------------
*/

$bannerStmt =
    $pdo->query(
        "SELECT *
         FROM banners
         WHERE status = 'active'
         ORDER BY sort_order ASC, created_at DESC"
    );

$banners =
    $bannerStmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Get Categories
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT
        id,
        name,
        slug,
        description,
        image
     FROM categories
     WHERE status = 'active'
     ORDER BY name ASC
     LIMIT 6"
);

$stmt->execute();

$categories = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Get Featured Products
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT
        p.id,
        p.name,
        p.slug,
        p.description,
        p.price,
        p.stock,
        p.image,
        c.name AS category_name,
        v.store_name
     FROM products p

     INNER JOIN categories c
        ON p.category_id = c.id

     INNER JOIN vendors v
        ON p.vendor_id = v.id

     WHERE p.status = 'active'

     AND p.featured = 1

     AND p.stock > 0

     AND c.status = 'active'

     AND v.status = 'approved'

     ORDER BY p.created_at DESC

     LIMIT 8"
);

$stmt->execute();

$featuredProducts = $stmt->fetchAll();


require_once "includes/header.php";

?>


<main>


    <!-- Hero Section -->
<!-- Hero Section -->

<?php if (!empty($banners)): ?>

    <section class="hero-banners">

        <div
            id="homeBannerCarousel"
            class="carousel slide"
            data-bs-ride="carousel"
        >

            <div class="carousel-indicators">

                <?php foreach ($banners as $index => $banner): ?>

                    <button
                        type="button"
                        data-bs-target="#homeBannerCarousel"
                        data-bs-slide-to="<?= $index ?>"
                        class="<?= $index === 0 ? "active" : "" ?>"
                        aria-current="<?= $index === 0 ? "true" : "false" ?>"
                    ></button>

                <?php endforeach; ?>

            </div>

            <div class="carousel-inner">

                <?php foreach ($banners as $index => $banner): ?>

                    <div class="carousel-item <?= $index === 0 ? "active" : "" ?>">

                        <img
                            src="<?= BASE_URL ?>uploads/banners/<?= e($banner["image"]) ?>"
                            class="d-block w-100"
                            style="max-height: 420px; object-fit: cover;"
                            alt="<?= e($banner["title"]) ?>"
                        >

                        <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded p-3">

                            <h2 class="fw-bold"><?= e($banner["title"]) ?></h2>

                            <?php if (!empty($banner["subtitle"])): ?>
                                <p><?= e($banner["subtitle"]) ?></p>
                            <?php endif; ?>

                            <?php if (!empty($banner["button_text"]) && !empty($banner["button_link"])): ?>
                                <a
                                    href="<?= e($banner["button_link"]) ?>"
                                    class="btn btn-light btn-sm"
                                >
                                    <?= e($banner["button_text"]) ?>
                                </a>
                            <?php endif; ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

            <?php if (count($banners) > 1): ?>

                <button
                    class="carousel-control-prev"
                    type="button"
                    data-bs-target="#homeBannerCarousel"
                    data-bs-slide="prev"
                >
                    <span class="carousel-control-prev-icon"></span>
                </button>

                <button
                    class="carousel-control-next"
                    type="button"
                    data-bs-target="#homeBannerCarousel"
                    data-bs-slide="next"
                >
                    <span class="carousel-control-next-icon"></span>
                </button>

            <?php endif; ?>

        </div>

    </section>

<?php else: ?>

    <section class="hero-section bg-light">

        <div class="container">

            <div class="row align-items-center">

                <div class="col-lg-7">

                    <h1 class="display-4 fw-bold">

                        Shop from
                        Multiple Vendors
                        in One Place

                    </h1>


                    <p class="lead text-muted">

                        Discover amazing products
                        from trusted sellers.

                    </p>


                    <a
                        href="<?= BASE_URL ?>search.php"
                        class="btn btn-dark btn-lg"
                    >

                        Shop Now

                        <i class="bi bi-arrow-right"></i>

                    </a>

                </div>


                <div class="col-lg-5 text-center">

                    <i
                        class="bi bi-shop display-1"
                    ></i>

                </div>

            </div>

        </div>

    </section>

<?php endif; ?> 
   

    <!-- Categories -->

    <section class="py-5">

        <div class="container">

            <h2 class="section-title text-center">

                Shop by Category

            </h2>


            <div class="row row-cols-2 row-cols-md-3 row-cols-lg-6 g-4">

                <?php foreach ($categories as $category): ?>

                    <div class="col">

                        <a
                            href="<?= BASE_URL ?>search.php?category_id=<?= (int) $category["id"] ?>"
                            class="text-decoration-none"
                        >

                            <div class="card h-100 text-center border-0 shadow-sm">

                                <?php if (!empty($category["image"])): ?>

                                    <img
                                        src="<?= e(categoryImageUrl($category["image"])) ?>"
                                        class="card-img-top"
                                        style="height: 150px; object-fit: cover;"
                                        alt="<?= e($category["name"]) ?>"
                                    >

                                <?php else: ?>

                                    <div class="py-5 bg-light">

                                        <i class="bi bi-grid display-5"></i>

                                    </div>

                                <?php endif; ?>


                                <div class="card-body">

                                    <h6 class="text-dark">

                                        <?= e($category["name"]) ?>

                                    </h6>

                                </div>

                            </div>

                        </a>

                    </div>

                <?php endforeach; ?>

            </div>

        </div>

    </section>


    <!-- Featured Products -->

    <section class="py-5 bg-light">

        <div class="container">

            <div class="d-flex justify-content-between align-items-center mb-4">

                <h2 class="section-title mb-0">

                    Featured Products

                </h2>


                <a
                    href="<?= BASE_URL ?>search.php"
                    class="btn btn-outline-dark"
                >

                    View All

                </a>

            </div>


            <?php if (!empty($featuredProducts)): ?>

                <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-4">

                    <?php foreach ($featuredProducts as $product): ?>

                        <?php

                        require __DIR__ . "/includes/product-card.php";

                        ?>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="alert alert-info">

                    No featured products available.

                </div>

            <?php endif; ?>

        </div>

    </section>


</main>


<?php

require_once "includes/footer.php";

?>