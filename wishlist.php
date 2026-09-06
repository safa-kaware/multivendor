
<?php

require_once "config/app.php";

requireLogin();


/*
|--------------------------------------------------------------------------
| ONLY CUSTOMERS CAN ACCESS WISHLIST
|--------------------------------------------------------------------------
*/

if (
    currentUserRole()
    !== "customer"
) {

    http_response_code(403);

    die(
        "Only customers can use the wishlist."
    );

}


/*
|--------------------------------------------------------------------------
| PAGE TITLE
|--------------------------------------------------------------------------
*/

$pageTitle =
    "My Wishlist | "
    . APP_NAME;


/*
|--------------------------------------------------------------------------
| CURRENT USER
|--------------------------------------------------------------------------
*/

$userId =
    currentUserId();


/*
|--------------------------------------------------------------------------
| REMOVE PRODUCT FROM WISHLIST
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"]
    === "POST"

    &&

    isset(
        $_POST["remove_wishlist"]
    )
) {

    $productId =
        (int)
        (
            $_POST["product_id"]
            ?? 0
        );


    if (
        $productId > 0
    ) {

        $stmt =
            $pdo->prepare(

                "DELETE FROM wishlist

                 WHERE user_id = ?

                 AND product_id = ?"

            );


        $stmt->execute([

            $userId,

            $productId

        ]);

    }


    redirect(
        BASE_URL
        . "wishlist.php"
    );

}


/*
|--------------------------------------------------------------------------
| GET WISHLIST PRODUCTS
|--------------------------------------------------------------------------
*/

$stmt =
    $pdo->prepare(

        "SELECT

            w.id AS wishlist_id,

            p.id,

            p.name,

            p.slug,

            p.description,

            p.price,

            p.stock,

            p.image,

            p.featured,

            c.name AS category_name,

            v.store_name

         FROM wishlist w

         INNER JOIN products p

            ON w.product_id = p.id

         INNER JOIN categories c

            ON p.category_id = c.id

         INNER JOIN vendors v

            ON p.vendor_id = v.id

         WHERE w.user_id = ?

         AND p.status = 'active'

         AND v.status = 'approved'

         ORDER BY w.created_at DESC"

    );


$stmt->execute([

    $userId

]);


$wishlistProducts =
    $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

require_once
    "includes/header.php";

?>


<div class="container py-5">


    <!-- PAGE HEADER -->

    <div
        class="d-flex justify-content-between align-items-center mb-4"
    >

        <div>

            <h1 class="fw-bold">

                My Wishlist ❤️

            </h1>

            <p class="text-muted">

                Products you have saved for later.

            </p>

        </div>


        <span class="badge text-bg-dark fs-6">

            <?= count(
                $wishlistProducts
            ) ?>

            item(s)

        </span>

    </div>



    <?php if (
        empty(
            $wishlistProducts
        )
    ): ?>


        <!-- EMPTY WISHLIST -->

        <div
            class="text-center py-5"
        >


            <div
                class="mb-4"
                style="font-size: 70px;"
            >

                ❤️

            </div>


            <h3>

                Your wishlist is empty

            </h3>


            <p class="text-muted">

                Save products you love and find them here later.

            </p>


            <a
                href="<?= BASE_URL ?>search.php"
                class="btn btn-primary"
            >

                Browse Products

            </a>


        </div>


    <?php else: ?>


        <!-- WISHLIST PRODUCTS -->

        <div class="row g-4">


            <?php foreach (
                $wishlistProducts
                as $product
            ): ?>


                <div
                    class="col-md-6 col-lg-4 col-xl-3"
                >


                    <div
                        class="card h-100 border-0 shadow-sm"
                    >


                        <!-- PRODUCT IMAGE -->

                        <a
                            href="<?= BASE_URL ?>product.php?slug=<?= urlencode($product["slug"]) ?>"
                        >


                            <?php if (
                                !empty(
                                    $product["image"]
                                )
                            ): ?>


                       <img
    src="<?= e(
        productImageUrl($product["image"])
    ) ?>"
    class="card-img-top" e(
                                        $product["name"]
                                    ) ?>"
                                    style="
                                        height:220px;
                                        object-fit:cover;
                                    "
                                >


                            <?php else: ?>


                                <div
                                    class="bg-light d-flex align-items-center justify-content-center"
                                    style="
                                        height:220px;
                                    "
                                >

                                    <i
                                        class="bi bi-image fs-1 text-muted"
                                    ></i>

                                </div>


                            <?php endif; ?>


                        </a>



                        <!-- PRODUCT INFORMATION -->

                        <div
                            class="card-body"
                        >


                            <small
                                class="text-muted"
                            >

                                <?= e(
                                    $product[
                                        "category_name"
                                    ]
                                ) ?>

                            </small>


                            <h5
                                class="card-title mt-2"
                            >


                                <!-- IMPORTANT:
                                     USE SLUG, NOT ID -->

                                <a
                                    href="<?= BASE_URL ?>product.php?slug=<?= urlencode($product["slug"]) ?>"
                                    class="text-decoration-none text-dark"
                                >

                                    <?= e(
                                        $product[
                                            "name"
                                        ]
                                    ) ?>

                                </a>


                            </h5>


                            <small
                                class="text-muted"
                            >

                                Sold by:

                                <?= e(
                                    $product[
                                        "store_name"
                                    ]
                                ) ?>

                            </small>


                            <h5
                                class="fw-bold mt-3"
                            >

                                ₹<?= number_format(
                                    (float)
                                    $product[
                                        "price"
                                    ],
                                    2
                                ) ?>

                            </h5>


                            <?php if (
                                (int)
                                $product[
                                    "stock"
                                ]
                                > 0
                            ): ?>


                                <span
                                    class="badge text-bg-success"
                                >

                                    In Stock

                                </span>


                            <?php else: ?>


                                <span
                                    class="badge text-bg-danger"
                                >

                                    Out of Stock

                                </span>


                            <?php endif; ?>


                        </div>



                        <!-- ACTIONS -->

                        <div
                            class="card-footer bg-white border-0"
                        >


                            <div
                                class="d-grid gap-2"
                            >


                                <!-- IMPORTANT:
                                     USE SLUG, NOT ID -->

                                <a
                                    href="<?= BASE_URL ?>product.php?slug=<?= urlencode($product["slug"]) ?>"
                                    class="btn btn-dark"
                                >

                                    View Product

                                </a>


                                <!-- REMOVE FROM WISHLIST -->

                                <form
                                    method="POST"
                                >


                                    <input
                                        type="hidden"
                                        name="product_id"
                                        value="<?= (int) $product["id"] ?>"
                                    >


                                    <button
                                        type="submit"
                                        name="remove_wishlist"
                                        value="1"
                                        class="btn btn-outline-danger w-100"
                                    >

                                        <i
                                            class="bi bi-heartbreak"
                                        ></i>

                                        Remove

                                    </button>


                                </form>


                            </div>


                        </div>


                    </div>


                </div>


            <?php endforeach; ?>


        </div>


    <?php endif; ?>


</div>


<?php

/*
|--------------------------------------------------------------------------
| FOOTER
|--------------------------------------------------------------------------
*/

require_once
    "includes/footer.php";

?>
```
