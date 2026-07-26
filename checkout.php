<?php

require_once "config/app.php";


/*
|--------------------------------------------------------------------------
| REQUIRE CUSTOMER LOGIN
|--------------------------------------------------------------------------
*/

requireLogin();

$userId = currentUserId();

/*
|--------------------------------------------------------------------------
| CHECK CART
|--------------------------------------------------------------------------
*/

if (
    empty($_SESSION["cart"])
) {

    $_SESSION["checkout_error"] =
        "Your cart is empty.";

    redirect(
        BASE_URL . "cart.php"
    );

}


/*
|--------------------------------------------------------------------------
| INITIALIZE
|--------------------------------------------------------------------------
*/

$errors = [];

$subtotal = 0;

$shippingAmount = 0;

$discountAmount = 0;

$appliedCouponId = null;

$totalAmount = 0;

$checkoutItems = [];


/*
|--------------------------------------------------------------------------
| COUPON DISCOUNT HELPER
|--------------------------------------------------------------------------
*/

function getAppliedCouponDiscount($pdo, $subtotal, &$appliedCouponId = null)
{

    $discount = 0;

    $appliedCouponId = null;

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

            $appliedCouponId = (int) $couponRow["id"];

        } else {

            unset($_SESSION["coupon"]);

        }

    }

    return $discount;

}


/*
|--------------------------------------------------------------------------
| BUILD CHECKOUT ITEMS FROM NEW CART STRUCTURE
|--------------------------------------------------------------------------
*/

foreach (
    $_SESSION["cart"]
    as $cartKey => $cartItem
) {


    /*
    |--------------------------------------------------------------------------
    | VALIDATE CART ITEM STRUCTURE
    |--------------------------------------------------------------------------
    */

    if (
        !isset(
            $cartItem["product_id"],
            $cartItem["quantity"]
        )
    ) {

        $errors[] =
            "Invalid item found in cart.";

        continue;

    }


    $productId =
        (int)
        $cartItem["product_id"];


    $quantity =
        (int)
        $cartItem["quantity"];


    $variationIds =
        $cartItem["variation_ids"]
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
    | CLEAN VARIATION IDS
    |--------------------------------------------------------------------------
    */

    $variationIds =
        array_map(
            "intval",
            $variationIds
        );


    $variationIds =
        array_values(
            array_filter(
                $variationIds
            )
        );


    /*
    |--------------------------------------------------------------------------
    | BASIC QUANTITY VALIDATION
    |--------------------------------------------------------------------------
    */

    if (
        $quantity < 1
    ) {

        $errors[] =
            "Invalid quantity for a product.";

        continue;

    }


    /*
    |--------------------------------------------------------------------------
    | FETCH PRODUCT
    |--------------------------------------------------------------------------
    */

    $stmt =
        $pdo->prepare(
            "SELECT

                p.id,
                p.name,
                p.price,
                p.stock,
                p.image,
                p.vendor_id,
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
    | PRODUCT NOT FOUND
    |--------------------------------------------------------------------------
    */

    if (
        !$product
    ) {

        $errors[] =
            "A product in your cart is no longer available.";

        continue;

    }


    /*
    |--------------------------------------------------------------------------
    | FETCH SELECTED VARIATIONS
    |--------------------------------------------------------------------------
    */

    $selectedVariations = [];


    if (
        !empty(
            $variationIds
        )
    ) {


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


        /*
        | Product ID is first parameter
        */

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
                 )

                 ORDER BY attribute ASC"
            );


        $stmt->execute(
            $params
        );


        $selectedVariations =
            $stmt->fetchAll();


        /*
        |--------------------------------------------------------------------------
        | VALIDATE VARIATION IDS
        |--------------------------------------------------------------------------
        */

        if (
            count(
                $selectedVariations
            )
            !==
            count(
                $variationIds
            )
        ) {

            $errors[] =
                "Invalid product variation selected.";

            continue;

        }

    }


    /*
    |--------------------------------------------------------------------------
    | CHECK PRODUCT STOCK
    |--------------------------------------------------------------------------
    */

    if (
        $quantity
        >
        (int)
        $product["stock"]
    ) {

        $errors[] =
            $product["name"]
            . " has only "
            . $product["stock"]
            . " item(s) available.";

        continue;

    }


    /*
    |--------------------------------------------------------------------------
    | CHECK VARIATION STOCK
    |--------------------------------------------------------------------------
    */

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
                $product["name"]
                . " - "
                . $variation["attribute"]
                . ": "
                . $variation["value"]
                . " has only "
                . $variation["stock"]
                . " item(s) available.";

        }

    }


    /*
    |--------------------------------------------------------------------------
    | CALCULATE ITEM SUBTOTAL
    |--------------------------------------------------------------------------
    */

    $itemSubtotal =
        (float)
        $product["price"]
        *
        $quantity;


    $subtotal +=
        $itemSubtotal;


    /*
    |--------------------------------------------------------------------------
    | STORE CHECKOUT ITEM
    |--------------------------------------------------------------------------
    */

    $checkoutItems[] = [

        "cart_key"
            =>
        $cartKey,

        "product_id"
            =>
        $productId,

        "name"
            =>
        $product["name"],

        "price"
            =>
        (float)
        $product["price"],

        "quantity"
            =>
        $quantity,

        "stock"
            =>
        (int)
        $product["stock"],

        "vendor_id"
            =>
        (int)
        $product["vendor_id"],

        "store_name"
            =>
        $product["store_name"],

        "variation_ids"
            =>
        $variationIds,

        "variations"
            =>
        $selectedVariations,

        "item_subtotal"
            =>
        $itemSubtotal

    ];

}


/*
|--------------------------------------------------------------------------
| CALCULATE TOTAL
|--------------------------------------------------------------------------
*/

$discountAmount =
    getAppliedCouponDiscount(
        $pdo,
        $subtotal,
        $appliedCouponId
    );

$totalAmount =
    $subtotal
    + $shippingAmount
    - $discountAmount;


/*
|--------------------------------------------------------------------------
| GET CUSTOMER ADDRESSES
|--------------------------------------------------------------------------
*/

$addresses =
    getUserAddresses(
        $pdo,
        $userId
    );


/*
|--------------------------------------------------------------------------
| HANDLE ORDER SUBMISSION
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"]
    === "POST"
) {


    /*
    |--------------------------------------------------------------------------
    | ADDRESS
    |--------------------------------------------------------------------------
    */

    $addressId =
        (int)
        (
            $_POST["address_id"]
            ?? 0
        );


    /*
    |--------------------------------------------------------------------------
    | PAYMENT METHOD
    |--------------------------------------------------------------------------
    */

    $paymentMethod =
        $_POST["payment_method"]
        ?? "";


    /*
    |--------------------------------------------------------------------------
    | VALIDATE ADDRESS
    |--------------------------------------------------------------------------
    */

    $selectedAddress =
        getUserAddress(
            $pdo,
            $addressId,
            $userId
        );


    if (
        !$selectedAddress
    ) {

        $errors[] =
            "Please select a valid delivery address.";

    }


    /*
    |--------------------------------------------------------------------------
    | VALIDATE PAYMENT METHOD
    |--------------------------------------------------------------------------
    */

    $allowedPayments = [

        "cod",

        "stripe",

        "razorpay"

    ];


    if (
        !in_array(
            $paymentMethod,
            $allowedPayments,
            true
        )
    ) {

        $errors[] =
            "Please select a valid payment method.";

    }


    /*
    |--------------------------------------------------------------------------
    | STOP IF CART HAS ERRORS
    |--------------------------------------------------------------------------
    */

    if (
        empty(
            $checkoutItems
        )
    ) {

        $errors[] =
            "Your cart does not contain any valid products.";

    }


    /*
    |--------------------------------------------------------------------------
    | CREATE ORDER
    |--------------------------------------------------------------------------
    */

    if (
        empty($errors)
    ) {

        try {


            /*
            |--------------------------------------------------------------------------
            | START TRANSACTION
            |--------------------------------------------------------------------------
            */

            $pdo->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | RECHECK ALL PRODUCTS WITH ROW LOCK
            |--------------------------------------------------------------------------
            */

            foreach (
                $checkoutItems
                as $index
                =>
                $item
            ) {


                /*
                |--------------------------------------------------------------------------
                | LOCK PRODUCT
                |--------------------------------------------------------------------------
                */

                $stmt =
                    $pdo->prepare(
                        "SELECT

                            id,
                            vendor_id,
                            price,
                            stock,
                            status

                         FROM products

                         WHERE id = ?

                         FOR UPDATE"
                    );


                $stmt->execute([
                    $item["product_id"]
                ]);


                $currentProduct =
                    $stmt->fetch();


                if (
                    !$currentProduct
                ) {

                    throw new Exception(
                        "A product in your cart no longer exists."
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | CHECK PRODUCT STATUS
                |--------------------------------------------------------------------------
                */

                if (
                    $currentProduct["status"]
                    !==
                    "active"
                ) {

                    throw new Exception(
                        $item["name"]
                        . " is no longer available."
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | CHECK PRODUCT STOCK AGAIN
                |--------------------------------------------------------------------------
                */

                if (
                    $item["quantity"]
                    >
                    (int)
                    $currentProduct["stock"]
                ) {

                    throw new Exception(
                        $item["name"]
                        . " does not have enough stock."
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | UPDATE PRICE FROM DATABASE
                |--------------------------------------------------------------------------
                */

                $checkoutItems[
                    $index
                ]["price"] =
                    (float)
                    $currentProduct[
                        "price"
                    ];


                /*
                |--------------------------------------------------------------------------
                | CHECK VARIATION STOCK
                |--------------------------------------------------------------------------
                */

                if (
                    !empty(
                        $item[
                            "variation_ids"
                        ]
                    )
                ) {


                    foreach (
                        $item[
                            "variation_ids"
                        ]
                        as $variationId
                    ) {


                        /*
                        |--------------------------------------------------------------------------
                        | LOCK VARIATION
                        |--------------------------------------------------------------------------
                        */

                        $variationStmt =
                            $pdo->prepare(
                                "SELECT

                                    id,
                                    product_id,
                                    stock

                                 FROM product_variations

                                 WHERE id = ?

                                 AND product_id = ?

                                 FOR UPDATE"
                            );


                        $variationStmt->execute([

                            $variationId,

                            $item[
                                "product_id"
                            ]

                        ]);


                        $variation =
                            $variationStmt->fetch();


                        if (
                            !$variation
                        ) {

                            throw new Exception(
                                "A selected product variation is invalid."
                            );

                        }


                        /*
                        |--------------------------------------------------------------------------
                        | CHECK VARIATION STOCK
                        |--------------------------------------------------------------------------
                        */

                        if (
                            $item["quantity"]
                            >
                            (int)
                            $variation["stock"]
                        ) {

                            throw new Exception(
                                $item["name"]
                                . " does not have enough variation stock."
                            );

                        }

                    }

                }

            }


            /*
            |--------------------------------------------------------------------------
            | RECALCULATE SUBTOTAL
            |--------------------------------------------------------------------------
            */

            $subtotal = 0;


            foreach (
                $checkoutItems
                as $index
                =>
                $item
            ) {

                $itemSubtotal =
                    $item["price"]
                    *
                    $item["quantity"];


                $checkoutItems[
                    $index
                ]["item_subtotal"] =
                    $itemSubtotal;


                $subtotal +=
                    $itemSubtotal;

            }


            /*
            |--------------------------------------------------------------------------
            | RECALCULATE TOTAL
            |--------------------------------------------------------------------------
            */
$discountAmount =
    getAppliedCouponDiscount(
        $pdo,
        $subtotal,
        $appliedCouponId
    );

$totalAmount =
    $subtotal
    + $shippingAmount
    - $discountAmount;

            /*
            |--------------------------------------------------------------------------
            | PAYMENT STATUS
            |--------------------------------------------------------------------------
            */

            $paymentStatus =
                "pending";


            /*
            |--------------------------------------------------------------------------
            | ORDER STATUS
            |--------------------------------------------------------------------------
            */

            $orderStatus =
                "pending";


            /*
            |--------------------------------------------------------------------------
            | INSERT ORDER
            |--------------------------------------------------------------------------
            */

            $stmt =
                $pdo->prepare(
                    "INSERT INTO orders

                    (
                        user_id,
                        address_id,
                        total_amount,
                        discount_amount,
                        shipping_amount,
                        payment_method,
                        payment_status,
                        order_status
                    )

                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );


            $stmt->execute([

                $userId,

                $addressId,

                $totalAmount,

                $discountAmount,

                $shippingAmount,

                $paymentMethod,

                $paymentStatus,

                $orderStatus

            ]);


            /*
            |--------------------------------------------------------------------------
            | GET ORDER ID
            |--------------------------------------------------------------------------
            */

           $orderId =
                (int)
                $pdo->lastInsertId();


            /*
            |--------------------------------------------------------------------------
            | RECORD COUPON USAGE
            |--------------------------------------------------------------------------
            */

            if ($appliedCouponId !== null) {

                $usageStmt =
                    $pdo->prepare(
                        "INSERT INTO coupon_usage

                        (
                            coupon_id,
                            user_id,
                            order_id
                        )

                        VALUES (?, ?, ?)"
                    );

                $usageStmt->execute([
                    $appliedCouponId,
                    $userId,
                    $orderId
                ]);

                $incrementStmt =
                    $pdo->prepare(
                        "UPDATE coupons
                         SET used_count = used_count + 1
                         WHERE id = ?"
                    );

                $incrementStmt->execute([
                    $appliedCouponId
                ]);

                unset($_SESSION["coupon"]);

            }


            /*
            |--------------------------------------------------------------------------
            | INSERT ORDER ITEM
            |--------------------------------------------------------------------------
            */

            $itemStmt =
                $pdo->prepare(
                    "INSERT INTO order_items

                    (
                        order_id,
                        product_id,
                        vendor_id,
                        variation_id,
                        quantity,
                        price
                    )

                    VALUES (?, ?, ?, ?, ?, ?)"
                );


            /*
            |--------------------------------------------------------------------------
            | REDUCE PRODUCT STOCK
            |--------------------------------------------------------------------------
            */

            $productStockStmt =
                $pdo->prepare(
                    "UPDATE products

                     SET stock =
                        stock - ?

                     WHERE id = ?

                     AND stock >= ?"
                );


            /*
            |--------------------------------------------------------------------------
            | REDUCE VARIATION STOCK
            |--------------------------------------------------------------------------
            */

            $variationStockStmt =
                $pdo->prepare(
                    "UPDATE product_variations

                     SET stock =
                        stock - ?

                     WHERE id = ?

                     AND product_id = ?

                     AND stock >= ?"
                );


            /*
            |--------------------------------------------------------------------------
            | PROCESS EACH CART ITEM
            |--------------------------------------------------------------------------
            */

            foreach (
                $checkoutItems
                as $item
            ) {


                /*
                |--------------------------------------------------------------------------
                | INSERT ORDER ITEMS
                |--------------------------------------------------------------------------
                |
                | One order item is created for
                | each selected variation.
                |
                */

                if (
                    !empty(
                        $item[
                            "variation_ids"
                        ]
                    )
                ) {


                    foreach (
                        $item[
                            "variation_ids"
                        ]
                        as $variationId
                    ) {


                        $itemStmt->execute([

                            $orderId,

                            $item[
                                "product_id"
                            ],

                            $item[
                                "vendor_id"
                            ],

                            $variationId,

                            $item[
                                "quantity"
                            ],

                            $item[
                                "price"
                            ]

                        ]);


                        /*
                        |--------------------------------------------------------------------------
                        | REDUCE VARIATION STOCK
                        |--------------------------------------------------------------------------
                        */

                        $variationStockStmt->execute([

                            $item[
                                "quantity"
                            ],

                            $variationId,

                            $item[
                                "product_id"
                            ],

                            $item[
                                "quantity"
                            ]

                        ]);


                        if (
                            $variationStockStmt
                            ->rowCount()
                            !== 1
                        ) {

                            throw new Exception(
                                "Unable to update variation stock."
                            );

                        }

                    }

                } else {


                    /*
                    |--------------------------------------------------------------------------
                    | PRODUCT WITHOUT VARIATION
                    |--------------------------------------------------------------------------
                    */

                    $itemStmt->execute([

                        $orderId,

                        $item[
                            "product_id"
                        ],

                        $item[
                            "vendor_id"
                        ],

                        null,

                        $item[
                            "quantity"
                        ],

                        $item[
                            "price"
                        ]

                    ]);

                }


                /*
                |--------------------------------------------------------------------------
                | REDUCE PRODUCT STOCK
                |--------------------------------------------------------------------------
                */

                $productStockStmt->execute([

                    $item[
                        "quantity"
                    ],

                    $item[
                        "product_id"
                    ],

                    $item[
                        "quantity"
                    ]

                ]);


                if (
                    $productStockStmt
                    ->rowCount()
                    !== 1
                ) {

                    throw new Exception(
                        "Unable to update product stock."
                    );

                }

            }


            /*
            |--------------------------------------------------------------------------
            | COMMIT TRANSACTION
            |--------------------------------------------------------------------------
            */

            $pdo->commit();


            /*
            |--------------------------------------------------------------------------
            | CLEAR CART
            |--------------------------------------------------------------------------
            */

            $_SESSION["cart"] = [];


            /*
            |--------------------------------------------------------------------------
            | STORE SUCCESS ORDER ID
            |--------------------------------------------------------------------------
            */

            $_SESSION[
                "order_success_id"
            ] =
                $orderId;


            /*
            |--------------------------------------------------------------------------
            | REDIRECT
            |--------------------------------------------------------------------------
            */

            redirect(
                BASE_URL
                . "order-success.php"
            );


        } catch (
            Exception $e
        ) {


            /*
            |--------------------------------------------------------------------------
            | ROLLBACK
            |--------------------------------------------------------------------------
            */

            if (
                $pdo->inTransaction()
            ) {

                $pdo->rollBack();

            }


            $errors[] =
                $e->getMessage();

        }

    }

}


$pageTitle =
    "Checkout | "
    . APP_NAME;


require_once "includes/header.php";

?>


<main>

<div class="container py-5">


<h1 class="fw-bold mb-4">

Checkout

</h1>


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


<form
    method="POST"
    action="<?= BASE_URL ?>checkout.php"
>


<div class="row g-4">


<!-- LEFT -->

<div class="col-lg-8">


<!-- ADDRESS -->

<div class="card border-0 shadow-sm mb-4">

<div class="card-body p-4">


<h4 class="fw-bold mb-4">

<i class="bi bi-geo-alt"></i>

Delivery Address

</h4>


<?php if (
    empty($addresses)
): ?>


<div class="alert alert-warning">

You don't have any saved addresses.


<br><br>


<a
    href="<?= BASE_URL ?>addresses.php"
    class="btn btn-dark"
>

Add Address

</a>

</div>


<?php else: ?>


<?php foreach (
    $addresses
    as $address
): ?>


<div
    class="form-check border rounded p-3 mb-3"
>


<input
    class="form-check-input ms-0 me-2"
    type="radio"
    name="address_id"
    value="<?= (int)
        $address["id"] ?>"
    id="address_<?= (int)
        $address["id"] ?>"
    <?= $address["is_default"]
        ? "checked"
        : "" ?>
    required
>


<label
    class="form-check-label"
    for="address_<?= (int)
        $address["id"] ?>"
>


<strong>

<?= e(
    $address["full_name"]
) ?>

</strong>


<?php if (
    $address["is_default"]
): ?>

<span
    class="badge text-bg-success"
>

Default

</span>

<?php endif; ?>


<br>


<?= e(
    $address["phone"]
) ?>


<br>


<?= e(
    $address["address_line"]
) ?>


<br>


<?= e(
    $address["city"]
) ?>,

<?= e(
    $address["state"]
) ?>

-

<?= e(
    $address["pincode"]
) ?>


<br>


<?= e(
    $address["country"]
) ?>


</label>


</div>


<?php endforeach; ?>


<a
    href="<?= BASE_URL ?>addresses.php"
    class="btn btn-outline-dark"
>

<i class="bi bi-plus"></i>

Add New Address

</a>


<?php endif; ?>


</div>

</div>



<!-- PAYMENT -->

<div class="card border-0 shadow-sm mb-4">

<div class="card-body p-4">


<h4 class="fw-bold mb-4">

<i class="bi bi-wallet2"></i>

Payment Method

</h4>


<!-- COD -->

<div
    class="form-check border rounded p-3 mb-3"
>


<input
    class="form-check-input ms-0 me-2"
    type="radio"
    name="payment_method"
    value="cod"
    id="payment_cod"
    required
>


<label
    class="form-check-label"
    for="payment_cod"
>

<strong>

Cash on Delivery

</strong>


<br>


<small class="text-muted">

Pay when your order is delivered.

</small>

</label>


</div>



<!-- STRIPE -->

<div
    class="form-check border rounded p-3 mb-3"
>


<input
    class="form-check-input ms-0 me-2"
    type="radio"
    name="payment_method"
    value="stripe"
    id="payment_stripe"
>


<label
    class="form-check-label"
    for="payment_stripe"
>

<strong>

Stripe

</strong>


<br>


<small class="text-muted">

Stripe payment integration will be added later.

</small>

</label>


</div>



<!-- RAZORPAY -->

<div
    class="form-check border rounded p-3"
>


<input
    class="form-check-input ms-0 me-2"
    type="radio"
    name="payment_method"
    value="razorpay"
    id="payment_razorpay"
>


<label
    class="form-check-label"
    for="payment_razorpay"
>

<strong>

Razorpay

</strong>


<br>


<small class="text-muted">

Razorpay integration will be added later.

</small>

</label>


</div>


<div
    class="alert alert-info mt-4 mb-0"
>

<strong>

Current stage:

</strong>


COD orders are processed immediately.


Stripe and Razorpay will be integrated in the payment gateway milestone.

</div>


</div>

</div>


</div>



<!-- RIGHT -->

<div class="col-lg-4">


<div class="card border-0 shadow-sm">

<div class="card-body p-4">


<h4 class="fw-bold mb-4">

Order Summary

</h4>


<?php foreach (
    $checkoutItems
    as $item
): ?>


<div
    class="border-bottom pb-3 mb-3"
>


<div
    class="d-flex justify-content-between"
>


<strong>

<?= e(
    $item["name"]
) ?>

</strong>


<span>

₹<?= number_format(
    $item["item_subtotal"],
    2
) ?>

</span>


</div>


<small class="text-muted">

<?= e(
    $item["store_name"]
) ?>


<br>


<?= (int)
    $item["quantity"] ?>

×


₹<?= number_format(
    $item["price"],
    2
) ?>


</small>


<?php if (
    !empty(
        $item["variations"]
    )
): ?>


<div class="mt-2">


<?php foreach (
    $item["variations"]
    as $variation
): ?>


<span
    class="badge bg-light text-dark me-1"
>

<?= e(
    $variation["attribute"]
) ?>

:

<?= e(
    $variation["value"]
) ?>

</span>


<?php endforeach; ?>


</div>


<?php endif; ?>


</div>


<?php endforeach; ?>


<hr>


<div
    class="d-flex justify-content-between"
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

</div>


<?php if ($discountAmount > 0): ?>

<div
    class="d-flex justify-content-between mt-2 text-success"
>

<span>

Coupon Discount

</span>


<strong>

-₹<?= number_format(
    $discountAmount,
    2
) ?>

</strong>

</div>

<?php endif; ?>


<div
    class="d-flex justify-content-between mt-2"
>

<span>

Shipping

</span>


<strong>

Free

</strong>

</div>


<hr>


<div
    class="d-flex justify-content-between"
>

<h5>

Total

</h5>


<h5>

₹<?= number_format(
    $totalAmount,
    2
) ?>

</h5>

</div>


<button
    type="submit"
    class="btn btn-dark w-100 mt-4"
    <?= empty($addresses)
        ? "disabled"
        : "" ?>
>

<i class="bi bi-check-circle"></i>

Place Order

</button>


</div>

</div>


</div>


</div>


</form>


</div>

</main>


<?php

require_once "includes/footer.php";

?>