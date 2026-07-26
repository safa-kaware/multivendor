<?php

require_once "config/app.php";


/*
|--------------------------------------------------------------------------
| REQUIRE CUSTOMER LOGIN
|--------------------------------------------------------------------------
*/

requireCustomer();

$userId = currentUserId();


/*
|--------------------------------------------------------------------------
| GET ORDER ID
|--------------------------------------------------------------------------
*/

$orderId =
    isset($_GET["id"])
        ? (int) $_GET["id"]
        : 0;


/*
|--------------------------------------------------------------------------
| VALIDATE ORDER ID
|--------------------------------------------------------------------------
*/

if ($orderId <= 0) {

    $_SESSION["checkout_error"] =
        "Invalid order.";

    redirect(
        BASE_URL . "my-orders.php"
    );

}


/*
|--------------------------------------------------------------------------
| FETCH ORDER
|--------------------------------------------------------------------------
|
| getUserOrder() automatically checks:
|
| order_id = requested order
| user_id  = logged-in customer
|
| Therefore, customers cannot view
| another customer's order.
|
|--------------------------------------------------------------------------
*/

$order =
    getUserOrder(
        $pdo,
        $orderId,
        $userId
    );


/*
|--------------------------------------------------------------------------
| ORDER NOT FOUND
|--------------------------------------------------------------------------
*/

if (!$order) {

    http_response_code(404);

    $pageTitle =
        "Order Not Found | "
        . APP_NAME;

    require_once "includes/header.php";

    ?>


    <div class="container py-5">

        <div
            class="card border-0 shadow-sm"
        >

            <div
                class="card-body text-center py-5"
            >

                <div
                    class="display-1 mb-3"
                >

                    📦

                </div>


                <h2 class="fw-bold">

                    Order Not Found

                </h2>


                <p class="text-muted">

                    The order you are looking for does not exist
                    or you do not have permission to view it.

                </p>


                <a
                    href="<?= BASE_URL ?>my-orders.php"
                    class="btn btn-dark"
                >

                    <i class="bi bi-arrow-left"></i>

                    Back to My Orders

                </a>

            </div>

        </div>

    </div>


    <?php

    require_once "includes/footer.php";

    exit;

}


/*
|--------------------------------------------------------------------------
| FETCH ORDER ITEMS
|--------------------------------------------------------------------------
*/

$orderItems =
    getOrderItems(
        $pdo,
        $orderId
    );


/*
|--------------------------------------------------------------------------
| CALCULATE ITEM TOTALS
|--------------------------------------------------------------------------
*/

$itemsSubtotal = 0;


foreach (
    $orderItems
    as &$item
) {

    $item["item_total"] =
        (float)
        $item["price"]
        *
        (int)
        $item["quantity"];


    $itemsSubtotal +=
        $item["item_total"];

}


unset($item);


/*
|--------------------------------------------------------------------------
| PAGE TITLE
|--------------------------------------------------------------------------
*/

$pageTitle =
    "Order #"
    . $orderId
    . " | "
    . APP_NAME;


require_once "includes/header.php";

?>


<div class="container py-5">


    <!-- PAGE HEADER -->

    <div
        class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3"
    >

        <div>

            <h1 class="fw-bold mb-1">

                Order #<?= (int) $order["id"] ?>

            </h1>


            <p class="text-muted mb-0">

                Placed on

                <?= date(
                    "d M Y",
                    strtotime(
                        $order["created_at"]
                    )
                ) ?>

                at

                <?= date(
                    "h:i A",
                    strtotime(
                        $order["created_at"]
                    )
                ) ?>

            </p>

        </div>


        <a
            href="<?= BASE_URL ?>my-orders.php"
            class="btn btn-outline-dark"
        >

            <i class="bi bi-arrow-left"></i>

            Back to My Orders

        </a>

    </div>



    <!-- ORDER STATUS -->

    <div
        class="card border-0 shadow-sm mb-4"
    >

        <div
            class="card-body p-4"
        >

            <div
                class="row g-4"
            >


                <!-- ORDER STATUS -->

                <div class="col-md-4">

                    <small
                        class="text-muted d-block mb-2"
                    >

                        Order Status

                    </small>


                    <?php if (
                        $order["order_status"]
                        === "pending"
                    ): ?>

                        <span
                            class="badge text-bg-warning fs-6"
                        >

                            Pending

                        </span>


                    <?php elseif (
                        $order["order_status"]
                        === "confirmed"
                    ): ?>

                        <span
                            class="badge text-bg-primary fs-6"
                        >

                            Confirmed

                        </span>


                    <?php elseif (
                        $order["order_status"]
                        === "processing"
                    ): ?>

                        <span
                            class="badge text-bg-info fs-6"
                        >

                            Processing

                        </span>


                    <?php elseif (
                        $order["order_status"]
                        === "shipped"
                    ): ?>

                        <span
                            class="badge text-bg-primary fs-6"
                        >

                            Shipped

                        </span>


                    <?php elseif (
                        $order["order_status"]
                        === "delivered"
                    ): ?>

                        <span
                            class="badge text-bg-success fs-6"
                        >

                            Delivered

                        </span>


                    <?php elseif (
                        $order["order_status"]
                        === "cancelled"
                    ): ?>

                        <span
                            class="badge text-bg-danger fs-6"
                        >

                            Cancelled

                        </span>


                    <?php else: ?>

                        <span
                            class="badge text-bg-secondary fs-6"
                        >

                            <?= e(
                                ucfirst(
                                    $order[
                                        "order_status"
                                    ]
                                )
                            ) ?>

                        </span>

                    <?php endif; ?>

                </div>



                <!-- PAYMENT METHOD -->

                <div class="col-md-4">

                    <small
                        class="text-muted d-block mb-2"
                    >

                        Payment Method

                    </small>


                    <strong>

                        <?php if (
                            $order[
                                "payment_method"
                            ]
                            === "cod"
                        ): ?>

                            Cash on Delivery

                        <?php elseif (
                            $order[
                                "payment_method"
                            ]
                            === "stripe"
                        ): ?>

                            Stripe

                        <?php elseif (
                            $order[
                                "payment_method"
                            ]
                            === "razorpay"
                        ): ?>

                            Razorpay

                        <?php else: ?>

                            <?= e(
                                $order[
                                    "payment_method"
                                ]
                            ) ?>

                        <?php endif; ?>

                    </strong>

                </div>



                <!-- PAYMENT STATUS -->

                <div class="col-md-4">

                    <small
                        class="text-muted d-block mb-2"
                    >

                        Payment Status

                    </small>


                    <?php if (
                        $order[
                            "payment_status"
                        ]
                        === "paid"
                    ): ?>

                        <span
                            class="badge text-bg-success fs-6"
                        >

                            Paid

                        </span>


                    <?php elseif (
                        $order[
                            "payment_status"
                        ]
                        === "failed"
                    ): ?>

                        <span
                            class="badge text-bg-danger fs-6"
                        >

                            Failed

                        </span>


                    <?php else: ?>

                        <span
                            class="badge text-bg-warning fs-6"
                        >

                            Pending

                        </span>

                    <?php endif; ?>

                </div>


            </div>

        </div>

    </div>



    <div class="row g-4">


        <!-- LEFT COLUMN -->

        <div class="col-lg-8">


            <!-- ORDER ITEMS -->

            <div
                class="card border-0 shadow-sm mb-4"
            >

                <div
                    class="card-body p-4"
                >

                    <h4
                        class="fw-bold mb-4"
                    >

                        <i class="bi bi-box-seam"></i>

                        Order Items

                    </h4>


                    <?php if (
                        empty($orderItems)
                    ): ?>


                        <div
                            class="alert alert-warning mb-0"
                        >

                            No items were found for this order.

                        </div>


                    <?php else: ?>


                        <?php foreach (
                            $orderItems
                            as $item
                        ): ?>


                            <div
                                class="row align-items-center border-bottom py-3"
                            >


                                <!-- PRODUCT IMAGE -->

                                <div
                                    class="col-3 col-md-2"
                                >

                                    <?php if (
                                        !empty(
                                            $item[
                                                "product_image"
                                            ]
                                        )
                                    ): ?>

                                        <img
                                            src="<?= BASE_URL ?>uploads/products/<?= e(
                                                $item[
                                                    "product_image"
                                                ]
                                            ) ?>"
                                            alt="<?= e(
                                                $item[
                                                    "product_name"
                                                ]
                                            ) ?>"
                                            class="img-fluid rounded"
                                            style="
                                                width: 80px;
                                                height: 80px;
                                                object-fit: cover;
                                            "
                                        >

                                    <?php else: ?>

                                        <div
                                            class="bg-light rounded d-flex align-items-center justify-content-center"
                                            style="
                                                width: 80px;
                                                height: 80px;
                                            "
                                        >

                                            <i
                                                class="bi bi-image text-muted fs-3"
                                            ></i>

                                        </div>

                                    <?php endif; ?>

                                </div>



                                <!-- PRODUCT INFORMATION -->

                                <div
                                    class="col-9 col-md-5"
                                >

                                    <h6
                                        class="fw-bold mb-1"
                                    >

                                        <?= e(
                                            $item[
                                                "product_name"
                                            ]
                                        ) ?>

                                    </h6>


                                    <p
                                        class="text-muted mb-1"
                                    >

                                        Sold by:

                                        <?= e(
                                            $item[
                                                "vendor_name"
                                            ]
                                        ) ?>

                                    </p>


                                    <?php if (
                                        !empty(
                                            $item[
                                                "variation_id"
                                            ]
                                        )
                                    ): ?>

                                        <small
                                            class="text-muted"
                                        >

                                            Variation ID:

                                            <?= (int)
                                                $item[
                                                    "variation_id"
                                                ] ?>

                                        </small>

                                    <?php endif; ?>

                                </div>



                                <!-- QUANTITY -->

                                <div
                                    class="col-4 col-md-2 mt-3 mt-md-0"
                                >

                                    <small
                                        class="text-muted d-block"
                                    >

                                        Quantity

                                    </small>


                                    <strong>

                                        <?= (int)
                                            $item[
                                                "quantity"
                                            ] ?>

                                    </strong>

                                </div>



                                <!-- PRICE -->

                                <div
                                    class="col-4 col-md-1 mt-3 mt-md-0"
                                >

                                    <small
                                        class="text-muted d-block"
                                    >

                                        Price

                                    </small>


                                    ₹<?= number_format(
                                        (float)
                                        $item[
                                            "price"
                                        ],
                                        2
                                    ) ?>

                                </div>



                                <!-- ITEM TOTAL -->

                                <div
                                    class="col-4 col-md-2 text-end mt-3 mt-md-0"
                                >

                                    <small
                                        class="text-muted d-block"
                                    >

                                        Total

                                    </small>


                                    <strong>

                                        ₹<?= number_format(
                                            (float)
                                            $item[
                                                "item_total"
                                            ],
                                            2
                                        ) ?>

                                    </strong>

                                </div>


                            </div>


                        <?php endforeach; ?>


                    <?php endif; ?>


                </div>

            </div>



            <!-- DELIVERY ADDRESS -->

            <div
                class="card border-0 shadow-sm"
            >

                <div
                    class="card-body p-4"
                >

                    <h4
                        class="fw-bold mb-4"
                    >

                        <i class="bi bi-geo-alt"></i>

                        Delivery Address

                    </h4>


                    <div
                        class="border rounded p-3"
                    >

                        <strong>

                            <?= e(
                                $order[
                                    "full_name"
                                ]
                            ) ?>

                        </strong>


                        <br>


                        <?= e(
                            $order[
                                "phone"
                            ]
                        ) ?>


                        <br><br>


                        <?= e(
                            $order[
                                "address_line"
                            ]
                        ) ?>


                        <br>


                        <?= e(
                            $order[
                                "city"
                            ]
                        ) ?>,

                        <?= e(
                            $order[
                                "state"
                            ]
                        ) ?>

                        -

                        <?= e(
                            $order[
                                "pincode"
                            ]
                        ) ?>


                        <br>


                        <?= e(
                            $order[
                                "country"
                            ]
                        ) ?>

                    </div>

                </div>

            </div>


        </div>



        <!-- RIGHT COLUMN -->

        <div class="col-lg-4">


            <!-- ORDER SUMMARY -->

            <div
                class="card border-0 shadow-sm mb-4"
            >

                <div
                    class="card-body p-4"
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

                            Items Subtotal

                        </span>


                        <strong>

                            ₹<?= number_format(
                                $itemsSubtotal,
                                2
                            ) ?>

                        </strong>

                    </div>


                    <div
                        class="d-flex justify-content-between mb-3"
                    >

                        <span>

                            Discount

                        </span>


                        <strong>

                            - ₹<?= number_format(
                                (float)
                                $order[
                                    "discount_amount"
                                ],
                                2
                            ) ?>

                        </strong>

                    </div>


                    <div
                        class="d-flex justify-content-between mb-3"
                    >

                        <span>

                            Shipping

                        </span>


                        <strong>

                            <?php if (
                                (float)
                                $order[
                                    "shipping_amount"
                                ]
                                > 0
                            ): ?>

                                ₹<?= number_format(
                                    (float)
                                    $order[
                                        "shipping_amount"
                                    ],
                                    2
                                ) ?>

                            <?php else: ?>

                                Free

                            <?php endif; ?>

                        </strong>

                    </div>


                    <hr>


                    <div
                        class="d-flex justify-content-between"
                    >

                        <strong
                            class="fs-5"
                        >

                            Total

                        </strong>


                        <strong
                            class="fs-5 text-primary"
                        >

                            ₹<?= number_format(
                                (float)
                                $order[
                                    "total_amount"
                                ],
                                2
                            ) ?>

                        </strong>

                    </div>


                </div>

            </div>



            <!-- PAYMENT INFORMATION -->

            <div
                class="card border-0 shadow-sm"
            >

                <div
                    class="card-body p-4"
                >

                    <h5
                        class="fw-bold mb-3"
                    >

                        Payment Information

                    </h5>


                    <p class="mb-2">

                        <strong>

                            Method:

                        </strong>


                        <?php if (
                            $order[
                                "payment_method"
                            ]
                            === "cod"
                        ): ?>

                            Cash on Delivery

                        <?php else: ?>

                            <?= e(
                                ucfirst(
                                    $order[
                                        "payment_method"
                                    ]
                                )
                            ) ?>

                        <?php endif; ?>

                    </p>


                    <p class="mb-0">

                        <strong>

                            Status:

                        </strong>


                        <?= e(
                            ucfirst(
                                $order[
                                    "payment_status"
                                ]
                            )
                        ) ?>

                    </p>

                </div>

            </div>


        </div>


    </div>


</div>


<?php

require_once "includes/footer.php";

?>