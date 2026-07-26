<?php

require_once "config/app.php";


/*
|--------------------------------------------------------------------------
| INITIALIZE CART
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["cart"])) {

    $_SESSION["cart"] = [];

}


/*
|--------------------------------------------------------------------------
| CART MESSAGE
|--------------------------------------------------------------------------
*/

$errors = [];

$success = "";


/*
|--------------------------------------------------------------------------
| UPDATE CART QUANTITY
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
) {

    $action =
        $_POST["action"]
        ?? "";


    /*
    |--------------------------------------------------------------------------
    | UPDATE QUANTITY
    |--------------------------------------------------------------------------
    */

    if (
        $action === "update"
    ) {

        $cartKey =
            $_POST["cart_key"]
            ?? "";

        $quantity =
            isset(
                $_POST["quantity"]
            )
                ? (int)
                    $_POST["quantity"]
                : 1;


        if (
            !isset(
                $_SESSION["cart"][
                    $cartKey
                ]
            )
        ) {

            $errors[] =
                "Cart item not found.";

        } elseif (
            $quantity < 1
        ) {

            $errors[] =
                "Quantity must be at least 1.";

        } else {

            $_SESSION["cart"][
                $cartKey
            ]["quantity"]
                = $quantity;

            $success =
                "Cart updated successfully.";

        }

    }


    /*
    |--------------------------------------------------------------------------
    | REMOVE ITEM
    |--------------------------------------------------------------------------
    */

    if (
        $action === "remove"
    ) {

        $cartKey =
            $_POST["cart_key"]
            ?? "";


        if (
            isset(
                $_SESSION["cart"][
                    $cartKey
                ]
            )
        ) {

            unset(
                $_SESSION["cart"][
                    $cartKey
                ]
            );

            $success =
                "Item removed from cart.";

        }

    }


    /*
    |--------------------------------------------------------------------------
    | CLEAR CART
    |--------------------------------------------------------------------------
    */

    if (
        $action === "clear"
    ) {

        $_SESSION["cart"] = [];

        $success =
            "Cart cleared successfully.";

    }

}


/*
|--------------------------------------------------------------------------
| FETCH CART PRODUCTS
|--------------------------------------------------------------------------
*/

$cartItems = [];

$subtotal = 0;


/*
|--------------------------------------------------------------------------
| PROCESS CART
|--------------------------------------------------------------------------
*/

foreach (
    $_SESSION["cart"]
    as $cartKey
    => $cartItem
) {


    /*
    |--------------------------------------------------------------------------
    | VALIDATE CART ITEM STRUCTURE
    |--------------------------------------------------------------------------
    */

    if (
        !isset(
            $cartItem["product_id"]
        )
    ) {

        continue;

    }


    $productId =
        (int)
        $cartItem[
            "product_id"
        ];


    $quantity =
        isset(
            $cartItem["quantity"]
        )
            ? (int)
                $cartItem["quantity"]
            : 1;


    $variationIds =
        $cartItem[
            "variation_ids"
        ]
        ?? [];


    if (
        !is_array(
            $variationIds
        )
    ) {

        $variationIds = [];

    }


    /*
    |--------------------------------------------------------------------------
    | FETCH PRODUCT
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare(
        "SELECT

            p.id,
            p.name,
            p.price,
            p.stock,
            p.image,
            p.status,

            v.store_name

         FROM products p

         INNER JOIN vendors v
            ON p.vendor_id = v.id

         WHERE p.id = ?

         AND p.status = 'active'

         AND v.status = 'approved'

         LIMIT 1"
    );


    $stmt->execute([
        $productId
    ]);


    $product =
        $stmt->fetch();


    /*
    |--------------------------------------------------------------------------
    | PRODUCT NO LONGER AVAILABLE
    |--------------------------------------------------------------------------
    */

    if (
        !$product
    ) {

        $errors[] =
            "One of the products in your cart is no longer available.";

        continue;

    }


    /*
    |--------------------------------------------------------------------------
    | FETCH VARIATIONS
    |--------------------------------------------------------------------------
    */

    $selectedVariations = [];


    if (
        !empty(
            $variationIds
        )
    ) {


        /*
        |--------------------------------------------------------------------------
        | CLEAN IDS
        |--------------------------------------------------------------------------
        */

        $variationIds =
            array_map(
                "intval",
                $variationIds
            );


        $variationIds =
            array_filter(
                $variationIds
            );


        /*
        |--------------------------------------------------------------------------
        | CREATE PLACEHOLDERS
        |--------------------------------------------------------------------------
        */

        $placeholders =
            implode(
                ",",
                array_fill(
                    0,
                    count(
                        $variationIds
                    ),
                    "?"
                )
            );


        $params =
            $variationIds;


        array_unshift(
            $params,
            $productId
        );


        /*
        |--------------------------------------------------------------------------
        | FETCH VARIATIONS
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

             AND id IN (
                $placeholders
             )

             ORDER BY attribute ASC"
        );


        $stmt->execute(
            $params
        );


        $selectedVariations =
            $stmt->fetchAll();

    }


    /*
    |--------------------------------------------------------------------------
    | CHECK PRODUCT STOCK
    |--------------------------------------------------------------------------
    */

    $availableStock =
        (int)
        $product[
            "stock"
        ];


    /*
    |--------------------------------------------------------------------------
    | CHECK VARIATION STOCK
    |--------------------------------------------------------------------------
    |
    | If a variation is selected,
    | use the lowest variation stock.
    |
    */

    foreach (
        $selectedVariations
        as $variation
    ) {

        $variationStock =
            (int)
            $variation[
                "stock"
            ];


        if (
            $variationStock
            <
            $availableStock
        ) {

            $availableStock =
                $variationStock;

        }

    }


    /*
    |--------------------------------------------------------------------------
    | STOCK WARNING
    |--------------------------------------------------------------------------
    */

    $stockWarning =
        "";


    if (
        $availableStock <= 0
    ) {

        $stockWarning =
            "Out of stock.";

    } elseif (
        $quantity
        >
        $availableStock
    ) {

        $stockWarning =
            "Only "
            . $availableStock
            . " available.";

    }


    /*
    |--------------------------------------------------------------------------
    | CALCULATE ITEM SUBTOTAL
    |--------------------------------------------------------------------------
    */

    $itemSubtotal =
        (float)
        $product[
            "price"
        ]
        *
        $quantity;


    $subtotal +=
        $itemSubtotal;


    /*
    |--------------------------------------------------------------------------
    | STORE CART ITEM
    |--------------------------------------------------------------------------
    */

    $cartItems[] = [

        "cart_key"
            =>
        $cartKey,

        "product"
            =>
        $product,

        "quantity"
            =>
        $quantity,

        "variations"
            =>
        $selectedVariations,

        "available_stock"
            =>
        $availableStock,

        "stock_warning"
            =>
        $stockWarning,

        "item_subtotal"
            =>
        $itemSubtotal

    ];

}


/*
|--------------------------------------------------------------------------
| TOTAL
|--------------------------------------------------------------------------
*/
/*
|--------------------------------------------------------------------------
| APPLY / REMOVE COUPON
|--------------------------------------------------------------------------
*/

if (
    isset($action)
    && $action === "apply_coupon"
) {

    $couponCode =
        strtoupper(
            trim(
                $_POST["coupon_code"]
                ?? ""
            )
        );

    if ($couponCode === "") {

        $errors[] =
            "Please enter a coupon code.";

    } else {

        $stmt =
            $pdo->prepare(
                "SELECT *
                 FROM coupons
                 WHERE code = ?"
            );

        $stmt->execute([$couponCode]);

        $couponRow =
            $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$couponRow) {

            $errors[] =
                "Invalid coupon code.";

        } elseif ($couponRow["status"] !== "active") {

            $errors[] =
                "This coupon is not active.";

        } elseif (
            strtotime($couponRow["expiry"]) < time()
        ) {

            $errors[] =
                "This coupon has expired.";

        } elseif (
            $couponRow["usage_limit"] !== null
            && $couponRow["used_count"] >= $couponRow["usage_limit"]
        ) {

            $errors[] =
                "This coupon has reached its usage limit.";

        } elseif (
            $subtotal < (float) $couponRow["minimum_amount"]
        ) {

            $errors[] =
                "Minimum order amount for this coupon is ₹"
                . number_format($couponRow["minimum_amount"], 2)
                . ".";

        } else {

            $_SESSION["coupon"] = [
                "id" => (int) $couponRow["id"],
                "code" => $couponRow["code"],
            ];

            $success =
                "Coupon \"" . $couponRow["code"] . "\" applied successfully.";

        }

    }

}

if (
    isset($action)
    && $action === "remove_coupon"
) {

    unset($_SESSION["coupon"]);

    $success =
        "Coupon removed.";

}


/*
|--------------------------------------------------------------------------
| CALCULATE DISCOUNT FROM APPLIED COUPON
|--------------------------------------------------------------------------
*/

$discount = 0;

$appliedCoupon = null;

if (isset($_SESSION["coupon"]["id"])) {

    $stmt =
        $pdo->prepare(
            "SELECT *
             FROM coupons
             WHERE id = ?
             AND status = 'active'"
        );

    $stmt->execute([$_SESSION["coupon"]["id"]]);

    $couponRow =
        $stmt->fetch(PDO::FETCH_ASSOC);

    if (
        $couponRow
        && strtotime($couponRow["expiry"]) >= time()
        && $subtotal >= (float) $couponRow["minimum_amount"]
        && (
            $couponRow["usage_limit"] === null
            || $couponRow["used_count"] < $couponRow["usage_limit"]
        )
    ) {

        if ($couponRow["discount_type"] === "percentage") {

            $discount =
                $subtotal
                * ((float) $couponRow["value"] / 100);

            if (
                $couponRow["maximum_discount"] !== null
                && $discount > (float) $couponRow["maximum_discount"]
            ) {

                $discount =
                    (float) $couponRow["maximum_discount"];

            }

        } else {

            $discount =
                (float) $couponRow["value"];

        }

        if ($discount > $subtotal) {

            $discount = $subtotal;

        }

        $appliedCoupon = $couponRow;

    } else {

        // Coupon no longer valid for current cart, drop it silently.
        unset($_SESSION["coupon"]);

    }

}


/*
|--------------------------------------------------------------------------
| TOTAL
|--------------------------------------------------------------------------
*/

$total =
    $subtotal
    - $discount;


/*
|--------------------------------------------------------------------------
| PAGE
|--------------------------------------------------------------------------
*/

$pageTitle =
    "Shopping Cart | "
    . APP_NAME;


require_once "includes/header.php";

?>


<div class="container py-5">


    <!-- PAGE TITLE -->

    <div
        class="d-flex justify-content-between align-items-center mb-4"
    >

        <div>

            <h1 class="fw-bold">

                Shopping Cart

            </h1>

            <p class="text-muted">

                Review your items before checkout.

            </p>

        </div>


        <?php if (
            !empty(
                $cartItems
            )
        ): ?>

            <form
                method="POST"
                onsubmit="
                    return confirm(
                        'Are you sure you want to clear your cart?'
                    );
                "
            >

                <input
                    type="hidden"
                    name="action"
                    value="clear"
                >


                <button
                    type="submit"
                    class="btn btn-outline-danger"
                >

                    Clear Cart

                </button>

            </form>

        <?php endif; ?>

    </div>



    <!-- ERRORS -->

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



    <!-- SUCCESS -->

    <?php if (
        $success !== ""
    ): ?>

        <div class="alert alert-success">

            <?= e(
                $success
            ) ?>

        </div>

    <?php endif; ?>



    <?php if (
        empty(
            $cartItems
        )
    ): ?>


        <!-- EMPTY CART -->

        <div
            class="text-center py-5"
        >

            <div
                class="display-1 mb-3"
            >

                🛒

            </div>


            <h3>

                Your cart is empty

            </h3>


            <p
                class="text-muted"
            >

                You haven't added any products yet.

            </p>


            <a
                href="index.php"
                class="btn btn-primary"
            >

                Continue Shopping

            </a>

        </div>


    <?php else: ?>


        <div class="row">


            <!-- CART ITEMS -->

            <div class="col-lg-8">


                <?php foreach (
                    $cartItems
                    as $item
                ): ?>


                    <div
                        class="card shadow-sm mb-3"
                    >

                        <div
                            class="card-body"
                        >

                            <div
                                class="row align-items-center"
                            >


                                <!-- IMAGE -->

                                <div
                                    class="col-md-2 mb-3 mb-md-0"
                                >

                                    <?php if (
                                        !empty(
                                            $item[
                                                "product"
                                            ][
                                                "image"
                                            ]
                                        )
                                    ): ?>

                                        <img
                                            src="uploads/products/<?=
                                                e(
                                                    $item[
                                                        "product"
                                                    ][
                                                        "image"
                                                    ]
                                                )
                                            ?>"
                                            class="img-fluid rounded"
                                            alt="<?= e(
                                                $item[
                                                    "product"
                                                ][
                                                    "name"
                                                ]
                                            ) ?>"
                                        >

                                    <?php else: ?>

                                        <div
                                            class="bg-light rounded p-3 text-center"
                                        >

                                            No Image

                                        </div>

                                    <?php endif; ?>

                                </div>



                                <!-- PRODUCT -->

                                <div
                                    class="col-md-4"
                                >

                                    <h5
                                        class="fw-bold"
                                    >

                                        <?= e(
                                            $item[
                                                "product"
                                            ][
                                                "name"
                                            ]
                                        ) ?>

                                    </h5>


                                    <p
                                        class="text-muted mb-1"
                                    >

                                        Sold by:

                                        <?= e(
                                            $item[
                                                "product"
                                            ][
                                                "store_name"
                                            ]
                                        ) ?>

                                    </p>


                                    <?php if (
                                        !empty(
                                            $item[
                                                "variations"
                                            ]
                                        )
                                    ): ?>

                                        <div
                                            class="mt-2"
                                        >

                                            <?php foreach (
                                                $item[
                                                    "variations"
                                                ]
                                                as $variation
                                            ): ?>

                                                <span
                                                    class="badge bg-light text-dark me-1"
                                                >

                                                    <?= e(
                                                        $variation[
                                                            "attribute"
                                                        ]
                                                    ) ?>

                                                    :

                                                    <?= e(
                                                        $variation[
                                                            "value"
                                                        ]
                                                    ) ?>

                                                </span>

                                            <?php endforeach; ?>

                                        </div>

                                    <?php endif; ?>

                                </div>



                                <!-- PRICE -->

                                <div
                                    class="col-md-2"
                                >

                                    <strong>

                                        ₹<?= number_format(
                                            (float)
                                            $item[
                                                "product"
                                            ][
                                                "price"
                                            ],
                                            2
                                        ) ?>

                                    </strong>

                                </div>



                                <!-- QUANTITY -->

                                <div
                                    class="col-md-2"
                                >

                                    <form
                                        method="POST"
                                    >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="update"
                                        >


                                        <input
                                            type="hidden"
                                            name="cart_key"
                                            value="<?= e(
                                                $item[
                                                    "cart_key"
                                                ]
                                            ) ?>"
                                        >


                                        <input
                                            type="number"
                                            name="quantity"
                                            class="form-control"
                                            min="1"
                                            value="<?= (int)
                                                $item[
                                                    "quantity"
                                                ] ?>"
                                            max="<?= (int)
                                                $item[
                                                    "available_stock"
                                                ] ?>"
                                            onchange="
                                                this.form.submit()
                                            "
                                        >

                                    </form>


                                    <?php if (
                                        $item[
                                            "stock_warning"
                                        ] !== ""
                                    ): ?>

                                        <small
                                            class="text-danger"
                                        >

                                            <?= e(
                                                $item[
                                                    "stock_warning"
                                                ]
                                            ) ?>

                                        </small>

                                    <?php endif; ?>

                                </div>



                                <!-- REMOVE -->

                                <div
                                    class="col-md-2 text-end"
                                >

                                    <form
                                        method="POST"
                                    >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="remove"
                                        >


                                        <input
                                            type="hidden"
                                            name="cart_key"
                                            value="<?= e(
                                                $item[
                                                    "cart_key"
                                                ]
                                            ) ?>"
                                        >


                                        <button
                                            type="submit"
                                            class="btn btn-outline-danger btn-sm"
                                        >

                                            Remove

                                        </button>

                                    </form>

                                </div>


                            </div>



                            <!-- ITEM SUBTOTAL -->

                            <div
                                class="text-end mt-3"
                            >

                                <strong>

                                    Item Total:

                                    ₹<?= number_format(
                                        $item[
                                            "item_subtotal"
                                        ],
                                        2
                                    ) ?>

                                </strong>

                            </div>


                        </div>

                    </div>


                <?php endforeach; ?>


            </div>



            <!-- ORDER SUMMARY -->

            <div
                class="col-lg-4"
            >


                <div
                    class="card shadow-sm"
                >

                    <div
                        class="card-body"
                    >

                        <h4
                            class="fw-bold mb-4"
                        >

                            Order Summary

                        </h4>


                        <div
                            class="d-flex justify-content-between mb-3"
                        >

                            <span>

                                Subtotal

                            </span>


                            <strong>

                                ₹<?= number_format(
                                    $subtotal,
                                    2
                                ) ?>

                            </strong>

                       

</strong>

                        </div>


                        <?php if ($appliedCoupon): ?>

                            <div
                                class="d-flex justify-content-between mb-3 text-success"
                            >

                                <span>

                                    Coupon (<?= e($appliedCoupon["code"]) ?>)

                                </span>


                                <strong>

                                    -₹<?= number_format(
                                        $discount,
                                        2
                                    ) ?>

                                </strong>

                            </div>

                        <?php endif; ?>


                        <div
                            class="d-flex justify-content-between mb-3"
                        >

                            <span>

                                Shipping

                            </span>


                            <strong>

                                Calculated at checkout

                            </strong>

                        </div>


                        <!-- COUPON FORM -->

                        <div class="mb-3">

                            <?php if ($appliedCoupon): ?>

                                <form method="POST">

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="remove_coupon"
                                    >

                                    <button
                                        type="submit"
                                        class="btn btn-outline-danger btn-sm w-100"
                                    >

                                        Remove Coupon

                                    </button>

                                </form>

                            <?php else: ?>

                                <form
                                    method="POST"
                                    class="d-flex gap-2"
                                >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="apply_coupon"
                                    >

                                    <input
                                        type="text"
                                        name="coupon_code"
                                        class="form-control form-control-sm text-uppercase"
                                        placeholder="Coupon code"
                                    >

                                    <button
                                        type="submit"
                                        class="btn btn-outline-dark btn-sm"
                                    >

                                        Apply

                                    </button>

                                </form>

                            <?php endif; ?>

                        </div>


                        <hr>


                        <div
                            class="d-flex justify-content-between mb-4"
                        >
                       

                            <strong>

                                Total

                            </strong>


                            <strong
                                class="text-primary fs-4"
                            >

                                ₹<?= number_format(
                                    $total,
                                    2
                                ) ?>

                            </strong>

                        </div>


                        <a
                            href="checkout.php"
                            class="btn btn-primary btn-lg w-100"
                        >

                            Proceed to Checkout

                        </a>


                        <a
                            href="index.php"
                            class="btn btn-outline-secondary w-100 mt-2"
                        >

                            Continue Shopping

                        </a>


                    </div>

                </div>


            </div>


        </div>


    <?php endif; ?>


</div>


<?php

require_once "includes/footer.php";

?>