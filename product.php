```php
<?php

require_once __DIR__ . "/config/app.php";


/*
|--------------------------------------------------------------------------
| GET PRODUCT
|--------------------------------------------------------------------------
|
| Supports:
|
| product.php?id=1
|
| AND
|
| product.php?slug=product-name
|
*/


$productId = 0;


/*
|--------------------------------------------------------------------------
| GET PRODUCT ID
|--------------------------------------------------------------------------
*/

if (
    isset($_GET["id"])
    &&
    is_numeric($_GET["id"])
) {

    $productId =
        (int) $_GET["id"];

}


/*
|--------------------------------------------------------------------------
| GET PRODUCT SLUG
|--------------------------------------------------------------------------
*/

$productSlug =
    trim(
        $_GET["slug"]
        ?? ""
    );


/*
|--------------------------------------------------------------------------
| FETCH PRODUCT BY SLUG
|--------------------------------------------------------------------------
*/

if (
    $productSlug !== ""
) {

    $stmt = $pdo->prepare(

        "SELECT

            p.id,
            p.vendor_id,
            p.category_id,
            p.name,
            p.slug,
            p.description,
            p.price,
            p.stock,
            p.sku,
            p.image,
            p.status,
            p.featured,

            v.store_name,

            c.name AS category_name

         FROM products p

         INNER JOIN vendors v
            ON p.vendor_id = v.id

         INNER JOIN categories c
            ON p.category_id = c.id

         WHERE p.slug = ?

         AND p.status = 'active'

         AND v.status = 'approved'

         LIMIT 1"

    );


    $stmt->execute([

        $productSlug

    ]);


/*
|--------------------------------------------------------------------------
| FETCH PRODUCT BY ID
|--------------------------------------------------------------------------
*/

} elseif (
    $productId > 0
) {

    $stmt = $pdo->prepare(

        "SELECT

            p.id,
            p.vendor_id,
            p.category_id,
            p.name,
            p.slug,
            p.description,
            p.price,
            p.stock,
            p.sku,
            p.image,
            p.status,
            p.featured,

            v.store_name,

            c.name AS category_name

         FROM products p

         INNER JOIN vendors v
            ON p.vendor_id = v.id

         INNER JOIN categories c
            ON p.category_id = c.id

         WHERE p.id = ?

         AND p.status = 'active'

         AND v.status = 'approved'

         LIMIT 1"

    );


    $stmt->execute([

        $productId

    ]);


/*
|--------------------------------------------------------------------------
| INVALID PRODUCT
|--------------------------------------------------------------------------
*/

} else {

    http_response_code(404);

    die(
        "Invalid product."
    );

}


/*
|--------------------------------------------------------------------------
| GET PRODUCT
|--------------------------------------------------------------------------
*/

$product =
    $stmt->fetch();


/*
|--------------------------------------------------------------------------
| CHECK PRODUCT
|--------------------------------------------------------------------------
*/

if (
    !$product
) {

    http_response_code(404);

    die(
        "Product not found or is no longer available."
    );

}


/*
|--------------------------------------------------------------------------
| ALWAYS USE ACTUAL PRODUCT ID
|--------------------------------------------------------------------------
*/

$productId =
    (int)
    $product["id"];


/*
|--------------------------------------------------------------------------
| FETCH PRODUCT VARIATIONS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(

    "SELECT

        id,
        attribute,
        value,
        stock

     FROM product_variations

     WHERE product_id = ?

     ORDER BY
        attribute ASC,
        value ASC"

);


$stmt->execute([

    $productId

]);


$variations =
    $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| GROUP VARIATIONS
|--------------------------------------------------------------------------
*/

$groupedVariations = [];


foreach (
    $variations
    as $variation
) {

    $attribute =
        $variation["attribute"];


    if (
        !isset(
            $groupedVariations[
                $attribute
            ]
        )
    ) {

        $groupedVariations[
            $attribute
        ] = [];

    }


    $groupedVariations[
        $attribute
    ][] = $variation;

}


/*
|--------------------------------------------------------------------------
| ADD TO CART
|--------------------------------------------------------------------------
*/

$errors = [];

$success = "";


if (
    $_SERVER["REQUEST_METHOD"]
    === "POST"

    &&

    !isset(
        $_POST["submit_review"]
    )
) {

    $quantity =
        isset(
            $_POST["quantity"]
        )

        ? (int)
            $_POST["quantity"]

        : 1;


    /*
    |--------------------------------------------------------------------------
    | VALIDATE QUANTITY
    |--------------------------------------------------------------------------
    */

    if (
        $quantity < 1
    ) {

        $errors[] =
            "Quantity must be at least 1.";

    }


    /*
    |--------------------------------------------------------------------------
    | GET SELECTED VARIATIONS
    |--------------------------------------------------------------------------
    */

    $selectedVariationIds =
        $_POST[
            "variation_ids"
        ]
        ?? [];


    if (
        !is_array(
            $selectedVariationIds
        )
    ) {

        $selectedVariationIds = [];

    }


    /*
    |--------------------------------------------------------------------------
    | CLEAN VARIATION IDS
    |--------------------------------------------------------------------------
    */

    $selectedVariationIds =
        array_map(
            "intval",
            $selectedVariationIds
        );


    $selectedVariationIds =
        array_filter(
            $selectedVariationIds
        );


    /*
    |--------------------------------------------------------------------------
    | VALIDATE REQUIRED VARIATIONS
    |--------------------------------------------------------------------------
    */

    if (
        !empty(
            $groupedVariations
        )
    ) {

        foreach (
            $groupedVariations
            as $attribute
            => $attributeVariations
        ) {

            $found =
                false;


            foreach (
                $attributeVariations
                as $variation
            ) {

                if (
                    in_array(
                        (int)
                        $variation["id"],
                        $selectedVariationIds,
                        true
                    )
                ) {

                    $found =
                        true;

                    break;

                }

            }


            if (
                !$found
            ) {

                $errors[] =
                    "Please select "
                    . $attribute
                    . ".";

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | FETCH SELECTED VARIATIONS
    |--------------------------------------------------------------------------
    */

    $selectedVariations = [];


    if (
        empty($errors)

        &&

        !empty(
            $selectedVariationIds
        )
    ) {

        $placeholders =
            implode(
                ",",
                array_fill(
                    0,
                    count(
                        $selectedVariationIds
                    ),
                    "?"
                )
            );


        $params =
            $selectedVariationIds;


        array_unshift(
            $params,
            $productId
        );


        $stmt =
            $pdo->prepare(

                "SELECT

                    id,
                    attribute,
                    value,
                    stock

                 FROM product_variations

                 WHERE product_id = ?

                 AND id IN (
                    $placeholders
                 )"

            );


        $stmt->execute(
            $params
        );


        $selectedVariations =
            $stmt->fetchAll();


        /*
        |--------------------------------------------------------------------------
        | VERIFY VARIATION IDS
        |--------------------------------------------------------------------------
        */

        if (
            count(
                $selectedVariations
            )
            !==
            count(
                $selectedVariationIds
            )
        ) {

            $errors[] =
                "Invalid product variation selected.";

        }

    }


    /*
    |--------------------------------------------------------------------------
    | CHECK VARIATION STOCK
    |--------------------------------------------------------------------------
    */

    if (
        empty($errors)

        &&

        !empty(
            $selectedVariations
        )
    ) {

        foreach (
            $selectedVariations
            as $variation
        ) {

            if (
                $quantity
                >
                (int)
                $variation["stock"]
            ) {

                $errors[] =
                    "Only "
                    . $variation["stock"]
                    . " units available for "
                    . $variation["attribute"]
                    . ": "
                    . $variation["value"]
                    . ".";

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | CHECK PRODUCT STOCK
    |--------------------------------------------------------------------------
    */

    if (
        empty($errors)

        &&

        $quantity
        >
        (int)
        $product["stock"]
    ) {

        $errors[] =
            "Only "
            . $product["stock"]
            . " units are available.";

    }


    /*
    |--------------------------------------------------------------------------
    | ADD TO CART
    |--------------------------------------------------------------------------
    */

    if (
        empty($errors)
    ) {

        if (
            !isset(
                $_SESSION["cart"]
            )
        ) {

            $_SESSION["cart"] = [];

        }


        /*
        |--------------------------------------------------------------------------
        | SORT VARIATIONS
        |--------------------------------------------------------------------------
        */

        sort(
            $selectedVariationIds
        );


        /*
        |--------------------------------------------------------------------------
        | CREATE CART KEY
        |--------------------------------------------------------------------------
        */

        $variationKey =
            implode(
                "-",
                $selectedVariationIds
            );


        $cartKey =
            $productId
            . "_"
            . (
                $variationKey
                ?: "none"
            );


        /*
        |--------------------------------------------------------------------------
        | ADD OR UPDATE CART
        |--------------------------------------------------------------------------
        */

        if (
            isset(
                $_SESSION["cart"][
                    $cartKey
                ]
            )
        ) {

            $_SESSION["cart"][
                $cartKey
            ]["quantity"]
            += $quantity;

        } else {

            $_SESSION["cart"][
                $cartKey
            ] = [

                "product_id"
                    =>
                $productId,

                "quantity"
                    =>
                $quantity,

                "variation_ids"
                    =>
                $selectedVariationIds

            ];

        }


        $success =
            "Product added to cart successfully.";

    }

}


/*
|--------------------------------------------------------------------------
| REVIEW VARIABLES
|--------------------------------------------------------------------------
*/

$reviewErrors = [];

$reviewSuccess = "";


/*
|--------------------------------------------------------------------------
| CURRENT USER
|--------------------------------------------------------------------------
*/

$currentUserId =
    currentUserId();


/*
|--------------------------------------------------------------------------
| PURCHASE / REVIEW STATUS
|--------------------------------------------------------------------------
*/

$hasPurchased =
    false;

$hasReviewed =
    false;


/*
|--------------------------------------------------------------------------
| CHECK PURCHASE
|--------------------------------------------------------------------------
*/

if (
    $currentUserId
) {

    $stmt =
        $pdo->prepare(

            "SELECT

                oi.id

             FROM order_items oi

             INNER JOIN orders o
                ON oi.order_id = o.id

             WHERE o.user_id = ?

             AND oi.product_id = ?

             AND o.payment_status IN
                ('paid', 'pending')

             AND o.order_status != 'cancelled'

             LIMIT 1"

        );


    $stmt->execute([

        $currentUserId,

        $productId

    ]);


    $hasPurchased =
        (bool)
        $stmt->fetch();


    /*
    |--------------------------------------------------------------------------
    | CHECK EXISTING REVIEW
    |--------------------------------------------------------------------------
    */

    $stmt =
        $pdo->prepare(

            "SELECT

                id

             FROM reviews

             WHERE product_id = ?

             AND user_id = ?

             LIMIT 1"

        );


    $stmt->execute([

        $productId,

        $currentUserId

    ]);


    $hasReviewed =
        (bool)
        $stmt->fetch();

}


/*
|--------------------------------------------------------------------------
| SUBMIT REVIEW
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"]
    === "POST"

    &&

    isset(
        $_POST["submit_review"]
    )
) {

    /*
    |--------------------------------------------------------------------------
    | REQUIRE LOGIN
    |--------------------------------------------------------------------------
    */

    if (
        !$currentUserId
    ) {

        $reviewErrors[] =
            "Please login to submit a review.";

    }


    /*
    |--------------------------------------------------------------------------
    | CHECK PURCHASE
    |--------------------------------------------------------------------------
    */

    if (
        $currentUserId
        &&
        !$hasPurchased
    ) {

        $reviewErrors[] =
            "You can only review products that you have purchased.";

    }


    /*
    |--------------------------------------------------------------------------
    | CHECK DUPLICATE REVIEW
    |--------------------------------------------------------------------------
    */

    if (
        $currentUserId
        &&
        $hasReviewed
    ) {

        $reviewErrors[] =
            "You have already reviewed this product.";

    }


    /*
    |--------------------------------------------------------------------------
    | RATING
    |--------------------------------------------------------------------------
    */

    $reviewRating =
        isset(
            $_POST["rating"]
        )

        ? (int)
            $_POST["rating"]

        : 0;


    /*
    |--------------------------------------------------------------------------
    | COMMENT
    |--------------------------------------------------------------------------
    */

    $reviewComment =
        trim(
            $_POST["comment"]
            ?? ""
        );


    /*
    |--------------------------------------------------------------------------
    | VALIDATE RATING
    |--------------------------------------------------------------------------
    */

    if (
        $reviewRating < 1

        ||

        $reviewRating > 5
    ) {

        $reviewErrors[] =
            "Please select a rating between 1 and 5 stars.";

    }


    /*
    |--------------------------------------------------------------------------
    | VALIDATE COMMENT
    |--------------------------------------------------------------------------
    */

    if (
        $reviewComment === ""
    ) {

        $reviewErrors[] =
            "Please write a review.";

    }


    /*
    |--------------------------------------------------------------------------
    | INSERT REVIEW
    |--------------------------------------------------------------------------
    */

    if (
        empty(
            $reviewErrors
        )
    ) {

        $stmt =
            $pdo->prepare(

                "INSERT INTO reviews

                (
                    product_id,
                    user_id,
                    rating,
                    comment,
                    status
                )

                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    'pending'
                )"

            );


        $stmt->execute([

            $productId,

            $currentUserId,

            $reviewRating,

            $reviewComment

        ]);


        $reviewSuccess =
            "Your review has been submitted and is awaiting approval.";


        $hasReviewed =
            true;

    }

}


/*
|--------------------------------------------------------------------------
| REVIEW STATISTICS
|--------------------------------------------------------------------------
*/

$stmt =
    $pdo->prepare(

        "SELECT

            COUNT(*) AS review_count,

            COALESCE(
                AVG(rating),
                0
            ) AS average_rating

         FROM reviews

         WHERE product_id = ?

         AND status = 'approved'"

    );


$stmt->execute([

    $productId

]);


$reviewStats =
    $stmt->fetch();


$averageRating =
    (float)
    (
        $reviewStats[
            "average_rating"
        ]
        ?? 0
    );


$reviewCount =
    (int)
    (
        $reviewStats[
            "review_count"
        ]
        ?? 0
    );


/*
|--------------------------------------------------------------------------
| RATING DISTRIBUTION
|--------------------------------------------------------------------------
*/

$ratingDistribution = [

    5 => 0,

    4 => 0,

    3 => 0,

    2 => 0,

    1 => 0

];


$stmt =
    $pdo->prepare(

        "SELECT

            rating,

            COUNT(*) AS total

         FROM reviews

         WHERE product_id = ?

         AND status = 'approved'

         GROUP BY rating"

    );


$stmt->execute([

    $productId

]);


$ratingRows =
    $stmt->fetchAll();


foreach (
    $ratingRows
    as $ratingRow
) {

    $ratingDistribution[
        (int)
        $ratingRow[
            "rating"
        ]
    ] =
        (int)
        $ratingRow[
            "total"
        ];

}


/*
|--------------------------------------------------------------------------
| APPROVED REVIEWS
|--------------------------------------------------------------------------
*/

$stmt =
    $pdo->prepare(

        "SELECT

            r.id,

            r.rating,

            r.comment,

            r.created_at,

            u.name AS user_name

         FROM reviews r

         INNER JOIN users u
            ON r.user_id = u.id

         WHERE r.product_id = ?

         AND r.status = 'approved'

         ORDER BY r.created_at DESC"

    );


$stmt->execute([

    $productId

]);


$reviews =
    $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| PAGE TITLE
|--------------------------------------------------------------------------
*/

$pageTitle =
    $product["name"]
    . " | "
    . APP_NAME;


/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

require_once
    __DIR__
    . "/includes/header.php";

?>


<div class="container py-5">


    <!-- ALERTS -->

    <?php if (
        !empty($errors)
    ): ?>

        <div class="alert alert-danger">

            <ul class="mb-0">

                <?php foreach (
                    $errors
                    as $error
                ): ?>

                    <li>

                        <?= e(
                            $error
                        ) ?>

                    </li>

                <?php endforeach; ?>

            </ul>

        </div>

    <?php endif; ?>


    <?php if (
        $success !== ""
    ): ?>

        <div class="alert alert-success">

            <?= e(
                $success
            ) ?>

            <a
                href="<?= BASE_URL ?>cart.php"
                class="alert-link ms-2"
            >

                View Cart

            </a>

        </div>

    <?php endif; ?>


    <!-- PRODUCT -->

    <div class="row">


        <!-- IMAGE -->

        <div class="col-md-6 mb-4">


            <?php if (
                !empty(
                    $product["image"]
                )
            ): ?>

                <img
                    src="<?= BASE_URL ?>uploads/products/<?= e(
                        $product["image"]
                    ) ?>"
                    class="img-fluid rounded shadow-sm"
                    alt="<?= e(
                        $product["name"]
                    ) ?>"
                >

            <?php else: ?>

                <div
                    class="bg-light rounded p-5 text-center"
                >

                    No Image Available

                </div>

            <?php endif; ?>


        </div>



        <!-- INFORMATION -->

        <div class="col-md-6">


            <p class="text-muted mb-2">

                <?= e(
                    $product[
                        "category_name"
                    ]
                ) ?>

            </p>


            <h1 class="fw-bold">

                <?= e(
                    $product[
                        "name"
                    ]
                ) ?>

            </h1>


            <p class="text-muted">

                Sold by:

                <strong>

                    <?= e(
                        $product[
                            "store_name"
                        ]
                    ) ?>

                </strong>

            </p>


            <h3 class="text-primary mb-3">

                ₹<?= number_format(
                    (float)
                    $product[
                        "price"
                    ],
                    2
                ) ?>

            </h3>


            <!-- RATING -->

            <div class="mb-4">


                <div
                    class="d-flex align-items-center gap-2"
                >


                    <span
                        class="text-warning fs-4"
                    >


                        <?php

                        $roundedRating =
                            round(
                                $averageRating
                            );

                        ?>


                        <?php for (
                            $i = 1;
                            $i <= 5;
                            $i++
                        ): ?>

                            <?= $i <= $roundedRating
                                ? "★"
                                : "☆"
                            ?>

                        <?php endfor; ?>


                    </span>


                    <strong>

                        <?= number_format(
                            $averageRating,
                            1
                        ) ?>

                        / 5

                    </strong>


                    <span
                        class="text-muted"
                    >

                        (<?= $reviewCount ?>
                        reviews)

                    </span>


                </div>


            </div>


            <!-- DESCRIPTION -->

            <div class="mb-4">

                <?= nl2br(
                    e(
                        $product[
                            "description"
                        ]
                    )
                ) ?>

            </div>



            <!-- ADD TO CART -->

            <form
                method="POST"
            >


                <!-- VARIATIONS -->

                <?php foreach (
                    $groupedVariations
                    as $attribute
                    => $attributeVariations
                ): ?>


                    <div class="mb-4">


                        <label
                            class="form-label fw-bold"
                        >

                            <?= e(
                                $attribute
                            ) ?>

                        </label>


                        <select
                            name="variation_ids[]"
                            class="form-select"
                            required
                        >


                            <option
                                value=""
                            >

                                Select
                                <?= e(
                                    $attribute
                                ) ?>

                            </option>


                            <?php foreach (
                                $attributeVariations
                                as $variation
                            ): ?>


                                <option
                                    value="<?= (int) $variation["id"] ?>"
                                    <?= (
                                        (int)
                                        $variation[
                                            "stock"
                                        ]
                                        <= 0
                                    )
                                        ? "disabled"
                                        : ""
                                    ?>
                                >

                                    <?= e(
                                        $variation[
                                            "value"
                                        ]
                                    ) ?>

                                    -

                                    <?= (int)
                                        $variation[
                                            "stock"
                                        ] ?>

                                    available

                                </option>


                            <?php endforeach; ?>


                        </select>


                    </div>


                <?php endforeach; ?>



                <!-- QUANTITY -->

                <div class="mb-4">


                    <label
                        class="form-label fw-bold"
                    >

                        Quantity

                    </label>


                    <input
                        type="number"
                        name="quantity"
                        class="form-control"
                        value="1"
                        min="1"
                        max="<?= (int) $product["stock"] ?>"
                        required
                    >


                </div>



                <!-- STOCK -->

                <p>


                    <?php if (
                        (int)
                        $product[
                            "stock"
                        ]
                        > 0
                    ): ?>


                        <span
                            class="badge bg-success"
                        >

                            In Stock

                        </span>


                        <span
                            class="text-muted ms-2"
                        >

                            <?= (int)
                                $product[
                                    "stock"
                                ] ?>

                            available

                        </span>


                    <?php else: ?>


                        <span
                            class="badge bg-danger"
                        >

                            Out of Stock

                        </span>


                    <?php endif; ?>


                </p>



                <!-- ADD TO CART BUTTON -->

                <button
                    type="submit"
                    class="btn btn-primary btn-lg w-100"
                    <?= (
                        (int)
                        $product[
                            "stock"
                        ]
                        <= 0
                    )
                        ? "disabled"
                        : ""
                    ?>
                >

                    Add to Cart

                </button>


            </form>


            <!-- WISHLIST -->

            <?php if (
                isLoggedIn()

                &&

                currentUserRole()
                === "customer"
            ): ?>


                <?php

                $inWishlist =
                    isInWishlist(
                        $pdo,
                        currentUserId(),
                        $productId
                    );

                ?>


                <form
                    method="POST"
                    action="<?= BASE_URL ?>wishlist-action.php"
                    class="mt-3"
                >


                    <input
                        type="hidden"
                        name="product_id"
                        value="<?= (int) $productId ?>"
                    >


                    <input
                        type="hidden"
                        name="action"
                        value="<?= $inWishlist
                            ? "remove"
                            : "add"
                        ?>"
                    >


                    <button
                        type="submit"
                        class="btn <?= $inWishlist
                            ? "btn-danger"
                            : "btn-outline-danger"
                        ?> w-100"
                    >


                        <i
                            class="bi bi-heart<?= $inWishlist
                                ? "-fill"
                                : ""
                            ?>"
                        ></i>


                        <?= $inWishlist
                            ? "Remove from Wishlist"
                            : "Add to Wishlist"
                        ?>


                    </button>


                </form>


            <?php endif; ?>


        </div>


    </div>



    <!-- PRODUCT DETAILS -->

    <div class="row mt-5">


        <div class="col-12">


            <hr>


            <h3 class="fw-bold">

                Product Details

            </h3>


            <p>

                SKU:

                <?= e(
                    $product[
                        "sku"
                    ]
                ) ?>

            </p>


            <p>

                Store:

                <?= e(
                    $product[
                        "store_name"
                    ]
                ) ?>

            </p>


        </div>


    </div>


</div>



<!-- REVIEW SECTION -->

<div class="container mt-5">


    <hr class="mb-5">


    <div class="row g-5">


        <!-- REVIEW SUMMARY -->

        <div class="col-lg-4">


            <h3 class="fw-bold">

                Customer Reviews

            </h3>


            <div
                class="text-warning fs-2"
            >


                <?php for (
                    $i = 1;
                    $i <= 5;
                    $i++
                ): ?>


                    <?= $i <= round(
                        $averageRating
                    )
                        ? "★"
                        : "☆"
                    ?>


                <?php endfor; ?>


            </div>


            <h4>

                <?= number_format(
                    $averageRating,
                    1
                ) ?>

                out of 5

            </h4>


            <p class="text-muted">

                Based on

                <?= $reviewCount ?>

                approved review(s)

            </p>



            <!-- RATING DISTRIBUTION -->

            <?php for (
                $rating = 5;
                $rating >= 1;
                $rating--
            ): ?>


                <?php

                $ratingTotal =
                    $ratingDistribution[
                        $rating
                    ];


                $percentage =
                    $reviewCount > 0

                    ? (
                        $ratingTotal
                        /
                        $reviewCount
                    )
                    * 100

                    : 0;

                ?>


                <div
                    class="d-flex align-items-center mb-2"
                >


                    <span
                        style="width:45px;"
                    >

                        <?= $rating ?> ★

                    </span>


                    <div
                        class="progress flex-grow-1 mx-2"
                        style="height:8px;"
                    >


                        <div
                            class="progress-bar"
                            style="width:<?= $percentage ?>%;"
                        ></div>


                    </div>


                    <small
                        class="text-muted"
                        style="width:30px;"
                    >

                        <?= $ratingTotal ?>

                    </small>


                </div>


            <?php endfor; ?>


        </div>



        <!-- REVIEW FORM -->

        <div class="col-lg-8">


            <?php if (
                !empty(
                    $reviewErrors
                )
            ): ?>


                <div
                    class="alert alert-danger"
                >


                    <ul
                        class="mb-0"
                    >


                        <?php foreach (
                            $reviewErrors
                            as $error
                        ): ?>


                            <li>

                                <?= e(
                                    $error
                                ) ?>

                            </li>


                        <?php endforeach; ?>


                    </ul>


                </div>


            <?php endif; ?>



            <?php if (
                $reviewSuccess
                !== ""
            ): ?>


                <div
                    class="alert alert-success"
                >

                    <?= e(
                        $reviewSuccess
                    ) ?>

                </div>


            <?php endif; ?>



            <?php if (
                !isLoggedIn()
            ): ?>


                <div
                    class="alert alert-info"
                >

                    Please

                    <a
                        href="<?= BASE_URL ?>login.php"
                    >

                        login

                    </a>

                    to write a review.

                </div>


            <?php elseif (
                !$hasPurchased
            ): ?>


                <div
                    class="alert alert-info"
                >

                    Only customers who have purchased this product can write a review.

                </div>


            <?php elseif (
                $hasReviewed
            ): ?>


                <div
                    class="alert alert-success"
                >

                    You have already submitted a review for this product.

                </div>


            <?php else: ?>


                <div
                    class="card border-0 shadow-sm"
                >


                    <div
                        class="card-body"
                    >


                        <h4
                            class="fw-bold mb-4"
                        >

                            Write a Review

                        </h4>


                        <form
                            method="POST"
                        >


                            <!-- RATING -->

                            <div
                                class="mb-4"
                            >


                                <label
                                    class="form-label fw-bold"
                                >

                                    Your Rating

                                </label>


                                <select
                                    name="rating"
                                    class="form-select"
                                    required
                                >


                                    <option
                                        value=""
                                    >

                                        Select Rating

                                    </option>


                                    <option
                                        value="5"
                                    >

                                        5 ★ — Excellent

                                    </option>


                                    <option
                                        value="4"
                                    >

                                        4 ★ — Very Good

                                    </option>


                                    <option
                                        value="3"
                                    >

                                        3 ★ — Good

                                    </option>


                                    <option
                                        value="2"
                                    >

                                        2 ★ — Poor

                                    </option>


                                    <option
                                        value="1"
                                    >

                                        1 ★ — Very Poor

                                    </option>


                                </select>


                            </div>



                            <!-- COMMENT -->

                            <div
                                class="mb-4"
                            >


                                <label
                                    class="form-label fw-bold"
                                >

                                    Your Review

                                </label>


                                <textarea
                                    name="comment"
                                    class="form-control"
                                    rows="5"
                                    maxlength="2000"
                                    placeholder="Share your experience with this product..."
                                    required
                                ></textarea>


                            </div>



                            <!-- SUBMIT -->

                            <button
                                type="submit"
                                name="submit_review"
                                value="1"
                                class="btn btn-primary"
                            >

                                Submit Review

                            </button>


                        </form>


                    </div>


                </div>


            <?php endif; ?>


        </div>


    </div>



    <!-- APPROVED REVIEWS -->

    <div
        class="row mt-5"
    >


        <div
            class="col-12"
        >


            <h4
                class="fw-bold mb-4"
            >

                Customer Feedback

            </h4>



            <?php if (
                empty(
                    $reviews
                )
            ): ?>


                <div
                    class="alert alert-light border"
                >

                    No approved reviews yet.

                    Be the first to review this product after purchasing it.

                </div>


            <?php else: ?>


                <?php foreach (
                    $reviews
                    as $review
                ): ?>


                    <div
                        class="card border-0 shadow-sm mb-3"
                    >


                        <div
                            class="card-body"
                        >


                            <div
                                class="d-flex justify-content-between"
                            >


                                <div>


                                    <strong>

                                        <?= e(
                                            $review[
                                                "user_name"
                                            ]
                                        ) ?>

                                    </strong>


                                    <div
                                        class="text-warning"
                                    >


                                        <?php for (
                                            $i = 1;
                                            $i <= 5;
                                            $i++
                                        ): ?>


                                            <?= $i <=
                                                (int)
                                                $review[
                                                    "rating"
                                                ]

                                                ? "★"
                                                : "☆"
                                            ?>


                                        <?php endfor; ?>


                                    </div>


                                </div>


                                <small
                                    class="text-muted"
                                >

                                    <?= date(
                                        "d M Y",
                                        strtotime(
                                            $review[
                                                "created_at"
                                            ]
                                        )
                                    ) ?>

                                </small>


                            </div>


                            <p
                                class="mt-3 mb-0"
                            >

                                <?= nl2br(
                                    e(
                                        $review[
                                            "comment"
                                        ]
                                    )
                                ) ?>


                            </p>


                        </div>


                    </div>


                <?php endforeach; ?>


            <?php endif; ?>


        </div>


    </div>


</div>



<?php

/*
|--------------------------------------------------------------------------
| FOOTER
|--------------------------------------------------------------------------
*/

require_once
    __DIR__
    . "/includes/footer.php";

?>
```
