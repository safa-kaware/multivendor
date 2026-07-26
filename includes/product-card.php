<?php

/*
|--------------------------------------------------------------------------
| PRODUCT CARD
|--------------------------------------------------------------------------
|
| Expected $product fields:
|
| id
| name
| slug
| description
| price
| stock
| image
| category_name
| store_name
|
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| PRODUCT URL
|--------------------------------------------------------------------------
*/

$productUrl =
    BASE_URL
    . "product.php?id="
    . (int) $product["id"];


/*
|--------------------------------------------------------------------------
| PRODUCT IMAGE
|--------------------------------------------------------------------------
*/

$productImage = "";

if (
    !empty($product["image"])
) {

    $productImage =
        productImageUrl(
            $product["image"]
        );

}

?>


<div class="col">


    <div
        class="card h-100 border-0 shadow-sm product-card"
    >


        <!-- PRODUCT IMAGE -->

        <a
            href="<?= e($productUrl) ?>"
            class="text-decoration-none"
        >

            <?php if (
                $productImage !== ""
            ): ?>

                <div class="product-image-wrapper">


                    <img
                        src="<?= e($productImage) ?>"
                        class="card-img-top product-image"
                        alt="<?= e($product["name"]) ?>"
                        loading="lazy"
                        onerror="this.onerror=null; this.src='<?= BASE_URL ?>assets/images/no-image.png';"
                    >


                </div>


            <?php else: ?>


                <div
                    class="product-image-fallback d-flex align-items-center justify-content-center"
                >

                    <div
                        class="text-center text-muted"
                    >

                        <i
                            class="bi bi-image fs-1"
                        ></i>

                        <p class="mb-0 mt-2">

                            No Image Available

                        </p>

                    </div>

                </div>


            <?php endif; ?>


        </a>


        <!-- PRODUCT INFORMATION -->

        <div
            class="card-body d-flex flex-column p-4"
        >


            <!-- CATEGORY -->

            <?php if (
                !empty(
                    $product["category_name"]
                )
            ): ?>

                <small
                    class="text-muted mb-2"
                >

                    <?= e(
                        $product["category_name"]
                    ) ?>

                </small>

            <?php endif; ?>


            <!-- PRODUCT NAME -->

            <h5
                class="card-title fw-bold"
            >

                <a
                    href="<?= e($productUrl) ?>"
                    class="text-decoration-none text-dark"
                >

                    <?= e(
                        $product["name"]
                    ) ?>

                </a>

            </h5>


            <!-- VENDOR -->

            <?php if (
                !empty(
                    $product["store_name"]
                )
            ): ?>

                <p
                    class="text-muted mb-2"
                >

                    Sold by:

                    <strong>

                        <?= e(
                            $product["store_name"]
                        ) ?>

                    </strong>

                </p>

            <?php endif; ?>


            <!-- RATING -->

            <div
                class="mb-3 text-warning"
            >

                <span>

                    ☆ ☆ ☆ ☆ ☆

                </span>

                <span
                    class="text-muted"
                >

                    (0)

                </span>

            </div>


            <!-- PRICE -->

            <h4
                class="fw-bold mb-3"
            >

                ₹<?= number_format(
                    (float)
                    $product["price"],
                    2
                ) ?>

            </h4>


            <!-- STOCK -->

            <div
                class="mb-3"
            >

                <?php if (
                    (int)
                    $product["stock"]
                    > 0
                ): ?>

                    <span
                        class="badge bg-success"
                    >

                        In Stock

                    </span>

                <?php else: ?>

                    <span
                        class="badge bg-danger"
                    >

                        Out of Stock

                    </span>

                <?php endif; ?>

            </div>


            <!-- VIEW PRODUCT -->

            <a
                href="<?= e($productUrl) ?>"
                class="btn btn-dark w-100 mt-auto"
            >

                View Product

            </a>


        </div>


    </div>


</div>


<style>

.product-card {
    transition:
        transform 0.2s ease,
        box-shadow 0.2s ease;

    overflow: hidden;
}

.product-card:hover {

    transform:
        translateY(-4px);

    box-shadow:
        0 0.75rem 1.5rem
        rgba(0, 0, 0, 0.12)
        !important;
}

.product-image-wrapper {

    width: 100%;

    height: 250px;

    overflow: hidden;

    background-color: #f8f9fa;
}

.product-image {

    width: 100%;

    height: 250px;

    object-fit: cover;

    display: block;

    transition:
        transform 0.3s ease;
}

.product-card:hover
.product-image {

    transform:
        scale(1.04);
}

.product-image-fallback {

    width: 100%;

    height: 250px;

    background-color:
        #f8f9fa;
}

</style>