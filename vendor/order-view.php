<?php

require_once "../config/app.php";


/*
|--------------------------------------------------------------------------
| REQUIRE APPROVED VENDOR
|--------------------------------------------------------------------------
*/

requireVendor($pdo);


/*
|--------------------------------------------------------------------------
| GET CURRENT VENDOR
|--------------------------------------------------------------------------
*/

$vendorId = currentVendorId($pdo);


if (!$vendorId) {

    http_response_code(403);

    die(
        "Access denied. Vendor account not found."
    );

}


/*
|--------------------------------------------------------------------------
| GET ORDER ID
|--------------------------------------------------------------------------
*/

$orderId =
    isset($_GET["id"])
        ? (int) $_GET["id"]
        : 0;


if (
    $orderId <= 0
) {

    http_response_code(400);

    die(
        "Invalid order ID."
    );

}


/*
|--------------------------------------------------------------------------
| FETCH ORDER
|--------------------------------------------------------------------------
|
| Important:
|
| We only allow the vendor to view an order
| if that order contains at least one item
| belonging to this vendor.
|
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(

    "SELECT DISTINCT

        o.id,
        o.user_id,
        o.address_id,

        o.total_amount,
        o.discount_amount,
        o.shipping_amount,

        o.payment_method,
        o.payment_status,
        o.order_status,

        o.created_at,

        u.name AS customer_name,
        u.email AS customer_email,

        a.full_name AS delivery_name,
        a.phone AS delivery_phone,
        a.address_line AS delivery_address,
        a.city AS delivery_city,
        a.state AS delivery_state,
        a.pincode AS delivery_pincode,
        a.country AS delivery_country

     FROM orders o

     INNER JOIN users u
        ON u.id = o.user_id

     INNER JOIN addresses a
        ON a.id = o.address_id

     INNER JOIN order_items oi
        ON oi.order_id = o.id

     WHERE o.id = ?

     AND oi.vendor_id = ?

     LIMIT 1"

);


$stmt->execute([

    $orderId,

    $vendorId

]);


$order =
    $stmt->fetch();


/*
|--------------------------------------------------------------------------
| ORDER NOT FOUND
|--------------------------------------------------------------------------
*/

if (
    !$order
) {

    http_response_code(404);

    die(
        "Order not found or you do not have access to this order."
    );

}


/*
|--------------------------------------------------------------------------
| FETCH ONLY THIS VENDOR'S ITEMS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(

    "SELECT

        oi.id,
        oi.order_id,
        oi.product_id,
        oi.vendor_id,
        oi.variation_id,
        oi.quantity,
        oi.price,

        p.name AS product_name,
        p.image AS product_image,

        pv.attribute AS variation_attribute,
        pv.value AS variation_value

     FROM order_items oi

     INNER JOIN products p
        ON p.id = oi.product_id

     LEFT JOIN product_variations pv
        ON pv.id = oi.variation_id

     WHERE oi.order_id = ?

     AND oi.vendor_id = ?

     ORDER BY oi.id ASC"

);


$stmt->execute([

    $orderId,

    $vendorId

]);


$orderItems =
    $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| CALCULATE VENDOR SUBTOTAL
|--------------------------------------------------------------------------
*/

$vendorSubtotal = 0;


foreach (
    $orderItems
    as $item
) {

    $vendorSubtotal +=

        (float)
        $item["price"]

        *

        (int)
        $item["quantity"];

}


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


/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

require_once "../includes/header.php";

?>


<div class="container py-5">


    <!-- PAGE HEADER -->

    <div
        class="d-flex justify-content-between align-items-center mb-4"
    >


        <div>

            <h1 class="fw-bold">

                Order #<?= (int)
                    $order["id"] ?>

            </h1>


            <p class="text-muted mb-0">

                Order placed on

                <?= date(
                    "d M Y, h:i A",
                    strtotime(
                        $order["created_at"]
                    )
                ) ?>

            </p>

        </div>


        <a
            href="<?= BASE_URL ?>vendor/orders.php"
            class="btn btn-outline-dark"
        >

            <i class="bi bi-arrow-left"></i>

            Back to Orders

        </a>


    </div>



    <!-- ORDER STATUS -->

    <div class="card border-0 shadow-sm mb-4">


        <div class="card-body p-4">


            <div
                class="row align-items-center"
            >


                <div class="col-md-6">


                    <h5 class="fw-bold mb-2">

                        Order Status

                    </h5>


                    <?php

                    $status =
                        $order[
                            "order_status"
                        ];

                    ?>


                    <?php if (
                        $status
                        === "pending"
                    ): ?>


                        <span
                            class="badge text-bg-warning fs-6"
                        >

                            Pending

                        </span>


                    <?php elseif (
                        $status
                        === "confirmed"
                    ): ?>


                        <span
                            class="badge text-bg-info fs-6"
                        >

                            Confirmed

                        </span>


                    <?php elseif (
                        $status
                        === "processing"
                    ): ?>


                        <span
                            class="badge text-bg-primary fs-6"
                        >

                            Processing

                        </span>


                    <?php elseif (
                        $status
                        === "shipped"
                    ): ?>


                        <span
                            class="badge text-bg-secondary fs-6"
                        >

                            Shipped

                        </span>


                    <?php elseif (
                        $status
                        === "delivered"
                    ): ?>


                        <span
                            class="badge text-bg-success fs-6"
                        >

                            Delivered

                        </span>


                    <?php elseif (
                        $status
                        === "cancelled"
                    ): ?>


                        <span
                            class="badge text-bg-danger fs-6"
                        >

                            Cancelled

                        </span>


                    <?php else: ?>


                        <span
                            class="badge text-bg-dark fs-6"
                        >

                            <?= e(
                                ucfirst(
                                    $status
                                )
                            ) ?>

                        </span>


                    <?php endif; ?>


                </div>


                <div class="col-md-6 text-md-end mt-3 mt-md-0">


                    <strong>

                        Payment Method:

                    </strong>


                    <?= e(
                        strtoupper(
                            $order[
                                "payment_method"
                            ]
                        )
                    ) ?>


                    <br>


                    <strong>

                        Payment Status:

                    </strong>


                    <?php if (
                        $order[
                            "payment_status"
                        ]
                        === "paid"
                    ): ?>


                        <span
                            class="text-success fw-bold"
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
                            class="text-danger fw-bold"
                        >

                            Failed

                        </span>


                    <?php else: ?>


                        <span
                            class="text-warning fw-bold"
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


            <!-- CUSTOMER INFORMATION -->

            <div
                class="card border-0 shadow-sm mb-4"
            >


                <div
                    class="card-body p-4"
                >


                    <h4 class="fw-bold mb-4">

                        <i
                            class="bi bi-person"
                        ></i>

                        Customer Information

                    </h4>


                    <div class="row">


                        <div class="col-md-6 mb-3">


                            <strong>

                                Name

                            </strong>


                            <br>


                            <?= e(
                                $order[
                                    "customer_name"
                                ]
                            ) ?>


                        </div>


                        <div class="col-md-6 mb-3">


                            <strong>

                                Email

                            </strong>


                            <br>


                            <?= e(
                                $order[
                                    "customer_email"
                                ]
                            ) ?>


                        </div>


                    </div>


                </div>


            </div>



            <!-- DELIVERY ADDRESS -->

            <div
                class="card border-0 shadow-sm mb-4"
            >


                <div
                    class="card-body p-4"
                >


                    <h4 class="fw-bold mb-4">

                        <i
                            class="bi bi-geo-alt"
                        ></i>

                        Delivery Address

                    </h4>


                    <strong>

                        <?= e(
                            $order[
                                "delivery_name"
                            ]
                        ) ?>

                    </strong>


                    <br>


                    <?= e(
                        $order[
                            "delivery_phone"
                        ]
                    ) ?>


                    <br><br>


                    <?= e(
                        $order[
                            "delivery_address"
                        ]
                    ) ?>


                    <br>


                    <?= e(
                        $order[
                            "delivery_city"
                        ]
                    ) ?>


                    ,


                    <?= e(
                        $order[
                            "delivery_state"
                        ]
                    ) ?>


                    -


                    <?= e(
                        $order[
                            "delivery_pincode"
                        ]
                    ) ?>


                    <br>


                    <?= e(
                        $order[
                            "delivery_country"
                        ]
                    ) ?>


                </div>


            </div>



            <!-- VENDOR ORDER ITEMS -->

            <div
                class="card border-0 shadow-sm"
            >


                <div
                    class="card-body p-4"
                >


                    <h4 class="fw-bold mb-4">

                        <i
                            class="bi bi-box-seam"
                        ></i>

                        Your Products in This Order

                    </h4>


                    <?php if (
                        empty(
                            $orderItems
                        )
                    ): ?>


                        <div
                            class="alert alert-warning"
                        >

                            No products belonging to your store
                            were found in this order.

                        </div>


                    <?php else: ?>


                        <?php foreach (
                            $orderItems
                            as $item
                        ): ?>


                            <div
                                class="row align-items-center border-bottom pb-3 mb-3"
                            >


                                <!-- PRODUCT IMAGE -->

                                <div
                                    class="col-md-2 mb-3 mb-md-0"
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
                                                class="bi bi-image text-muted"
                                            ></i>

                                        </div>


                                    <?php endif; ?>


                                </div>



                                <!-- PRODUCT DETAILS -->

                                <div
                                    class="col-md-5"
                                >


                                    <h5 class="fw-bold mb-1">

                                        <?= e(
                                            $item[
                                                "product_name"
                                            ]
                                        ) ?>

                                    </h5>


                                    <?php if (
                                        !empty(
                                            $item[
                                                "variation_attribute"
                                            ]
                                        )
                                    ): ?>


                                        <span
                                            class="badge bg-light text-dark"
                                        >

                                            <?= e(
                                                $item[
                                                    "variation_attribute"
                                                ]
                                            ) ?>


                                            :


                                            <?= e(
                                                $item[
                                                    "variation_value"
                                                ]
                                            ) ?>

                                        </span>


                                    <?php endif; ?>


                                </div>



                                <!-- QUANTITY -->

                                <div
                                    class="col-md-2"
                                >


                                    <small
                                        class="text-muted"
                                    >

                                        Quantity

                                    </small>


                                    <br>


                                    <strong>

                                        <?= (int)
                                            $item[
                                                "quantity"
                                            ] ?>

                                    </strong>


                                </div>



                                <!-- PRICE -->

                                <div
                                    class="col-md-3 text-md-end mt-3 mt-md-0"
                                >


                                    <small
                                        class="text-muted"
                                    >

                                        Item Total

                                    </small>


                                    <br>


                                    <strong>

                                        ₹<?= number_format(

                                            (float)
                                            $item[
                                                "price"
                                            ]

                                            *

                                            (int)
                                            $item[
                                                "quantity"
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


        </div>



        <!-- RIGHT COLUMN -->

        <div class="col-lg-4">


            <!-- VENDOR ORDER SUMMARY -->

            <div
                class="card border-0 shadow-sm mb-4"
            >


                <div
                    class="card-body p-4"
                >


                    <h4 class="fw-bold mb-4">

                        Order Summary

                    </h4>


                    <div
                        class="d-flex justify-content-between mb-3"
                    >


                        <span>

                            Your Products

                        </span>


                        <strong>

                            ₹<?= number_format(

                                $vendorSubtotal,

                                2

                            ) ?>

                        </strong>


                    </div>


                    <div
                        class="d-flex justify-content-between mb-3"
                    >


                        <span>

                            Total Items

                        </span>


                        <strong>

                            <?= count(
                                $orderItems
                            ) ?>

                        </strong>


                    </div>


                    <hr>


                    <div
                        class="d-flex justify-content-between"
                    >


                        <strong>

                            Vendor Subtotal

                        </strong>


                        <strong
                            class="fs-5"
                        >

                            ₹<?= number_format(

                                $vendorSubtotal,

                                2

                            ) ?>

                        </strong>


                    </div>


                    <small
                        class="text-muted d-block mt-3"
                    >

                        This amount represents only the products
                        belonging to your store.

                    </small>


                </div>


            </div>



            <!-- FULL ORDER TOTAL -->

            <div
                class="card border-0 shadow-sm"
            >


                <div
                    class="card-body p-4"
                >


                    <h5 class="fw-bold mb-3">

                        Complete Order Total

                    </h5>


                    <div
                        class="d-flex justify-content-between mb-2"
                    >

                        <span>

                            Order Total

                        </span>


                        <strong>

                            ₹<?= number_format(

                                (float)
                                $order[
                                    "total_amount"
                                ],

                                2

                            ) ?>

                        </strong>

                    </div>


                    <div
                        class="d-flex justify-content-between mb-2"
                    >

                        <span>

                            Shipping

                        </span>


                        <strong>

                            ₹<?= number_format(

                                (float)
                                $order[
                                    "shipping_amount"
                                ],

                                2

                            ) ?>

                        </strong>

                    </div>


                    <div
                        class="d-flex justify-content-between"
                    >

                        <span>

                            Discount

                        </span>


                        <strong>

                            ₹<?= number_format(

                                (float)
                                $order[
                                    "discount_amount"
                                ],

                                2

                            ) ?>

                        </strong>

                    </div>


                    <hr>


                    <div
                        class="d-flex justify-content-between"
                    >

                        <strong>

                            Customer Paid

                        </strong>


                        <strong
                            class="fs-5"
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


                    <small
                        class="text-muted d-block mt-3"
                    >

                        This is the complete order amount
                        paid or payable by the customer.

                    </small>


                </div>


            </div>


        </div>


    </div>


</div>


<?php

require_once "../includes/footer.php";

?>