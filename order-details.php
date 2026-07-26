<?php

require_once "config/app.php";


/*
|--------------------------------------------------------------------------
| Require Customer Login
|--------------------------------------------------------------------------
*/

requireCustomer();


/*
|--------------------------------------------------------------------------
| Get Current User
|--------------------------------------------------------------------------
*/

$userId = currentUserId();


/*
|--------------------------------------------------------------------------
| Get Order ID
|--------------------------------------------------------------------------
*/

$orderId = (int) (
    $_GET["id"] ?? 0
);


/*
|--------------------------------------------------------------------------
| Validate Order ID
|--------------------------------------------------------------------------
*/

if ($orderId <= 0) {

    redirect(
        BASE_URL . "profile.php"
    );

}


/*
|--------------------------------------------------------------------------
| Get Order
|--------------------------------------------------------------------------
*/

$order = getUserOrder(
    $pdo,
    $orderId,
    $userId
);


/*
|--------------------------------------------------------------------------
| Check Order Exists
|--------------------------------------------------------------------------
*/

if (!$order) {

    http_response_code(404);

    die("Order not found.");

}


/*
|--------------------------------------------------------------------------
| Get Order Items
|--------------------------------------------------------------------------
*/

$orderItems = getOrderItems(
    $pdo,
    $orderId
);


/*
|--------------------------------------------------------------------------
| Page Title
|--------------------------------------------------------------------------
*/

$pageTitle =
    "Order #"
    . $orderId
    . " | "
    . APP_NAME;


/*
|--------------------------------------------------------------------------
| Header
|--------------------------------------------------------------------------
*/

require_once "includes/header.php";

?>


<main>

    <div class="container py-5">


        <!--
        |--------------------------------------------------------------------------
        | Page Header
        |--------------------------------------------------------------------------
        -->

        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h1 class="fw-bold">

                    Order #<?= e(
                        $order["id"]
                    ) ?>

                </h1>


                <p class="text-muted mb-0">

                    Placed on

                    <?= date(
                        "d M Y, h:i A",
                        strtotime(
                            $order["created_at"]
                        )
                    ) ?>

                </p>

            </div>


            <a
                href="<?= BASE_URL ?>profile.php"
                class="btn btn-outline-dark"
            >

                ← Back to Orders

            </a>

        </div>


        <div class="row g-4">


            <!--
            |--------------------------------------------------------------------------
            | LEFT COLUMN
            |--------------------------------------------------------------------------
            -->

            <div class="col-lg-8">


                <!--
                |--------------------------------------------------------------------------
                | Order Items
                |--------------------------------------------------------------------------
                -->

                <div class="card border-0 shadow-sm mb-4">

                    <div class="card-body p-4">


                        <h4 class="fw-bold mb-4">

                            <i class="bi bi-box-seam"></i>

                            Order Items

                        </h4>


                        <?php if (
                            empty($orderItems)
                        ): ?>


                            <div class="alert alert-warning">

                                No items found for this order.

                            </div>


                        <?php else: ?>


                            <?php foreach (
                                $orderItems
                                as $item
                            ): ?>


                                <div
                                    class="row align-items-center border-bottom py-3"
                                >


                                    <!-- Product Image -->

                                    <div class="col-md-2">


                                        <?php if (
                                            !empty(
                                                $item["product_image"]
                                            )
                                        ): ?>


                                            <img
                                                src="<?= BASE_URL ?>uploads/products/<?= e(
                                                    $item["product_image"]
                                                ) ?>"
                                                alt="<?= e(
                                                    $item["product_name"]
                                                ) ?>"
                                                class="img-fluid rounded"
                                                style="
                                                    height: 80px;
                                                    width: 80px;
                                                    object-fit: cover;
                                                "
                                            >


                                        <?php else: ?>


                                            <div
                                                class="bg-light rounded d-flex align-items-center justify-content-center"
                                                style="
                                                    height: 80px;
                                                    width: 80px;
                                                "
                                            >

                                                <i
                                                    class="bi bi-image"
                                                    style="font-size: 30px;"
                                                ></i>

                                            </div>


                                        <?php endif; ?>


                                    </div>


                                    <!-- Product Information -->

                                    <div class="col-md-5">


                                        <h6 class="fw-bold">

                                            <?= e(
                                                $item["product_name"]
                                            ) ?>

                                        </h6>


                                        <small class="text-muted">

                                            Sold by:

                                            <?= e(
                                                $item["vendor_name"]
                                            ) ?>

                                        </small>


                                    </div>


                                    <!-- Quantity -->

                                    <div class="col-md-2">


                                        <span class="text-muted">

                                            Qty:

                                        </span>


                                        <?= e(
                                            $item["quantity"]
                                        ) ?>


                                    </div>


                                    <!-- Price -->

                                    <div class="col-md-3 text-end">


                                        <strong>

                                            ₹<?= number_format(
                                                $item["price"]
                                                * $item["quantity"],
                                                2
                                            ) ?>

                                        </strong>


                                    </div>


                                </div>


                            <?php endforeach; ?>


                        <?php endif; ?>


                    </div>

                </div>


                <!--
                |--------------------------------------------------------------------------
                | Delivery Address
                |--------------------------------------------------------------------------
                -->

                <div class="card border-0 shadow-sm">

                    <div class="card-body p-4">


                        <h4 class="fw-bold mb-4">

                            <i class="bi bi-geo-alt"></i>

                            Delivery Address

                        </h4>


                        <strong>

                            <?= e(
                                $order["full_name"]
                            ) ?>

                        </strong>


                        <br>


                        <?= e(
                            $order["phone"]
                        ) ?>


                        <br><br>


                        <?= e(
                            $order["address_line"]
                        ) ?>


                        <br>


                        <?= e(
                            $order["city"]
                        ) ?>,

                        <?= e(
                            $order["state"]
                        ) ?>

                        -

                        <?= e(
                            $order["pincode"]
                        ) ?>


                        <br>


                        <?= e(
                            $order["country"]
                        ) ?>


                    </div>

                </div>


            </div>


            <!--
            |--------------------------------------------------------------------------
            | RIGHT COLUMN
            |--------------------------------------------------------------------------
            -->

            <div class="col-lg-4">


                <!--
                |--------------------------------------------------------------------------
                | Order Summary
                |--------------------------------------------------------------------------
                -->

                <div class="card border-0 shadow-sm">

                    <div class="card-body p-4">


                        <h4 class="fw-bold mb-4">

                            Order Summary

                        </h4>


                        <?php

                        /*
                        |--------------------------------------------------------------------------
                        | Calculate Subtotal
                        |--------------------------------------------------------------------------
                        */

                        $subtotal = 0;


                        foreach (
                            $orderItems
                            as $item
                        ) {

                            $subtotal +=
                                $item["price"]
                                * $item["quantity"];

                        }

                        ?>


                        <!-- Subtotal -->

                        <div
                            class="d-flex justify-content-between mb-2"
                        >

                            <span>

                                Subtotal

                            </span>


                            <span>

                                ₹<?= number_format(
                                    $subtotal,
                                    2
                                ) ?>

                            </span>

                        </div>


                        <!-- Discount -->

                        <div
                            class="d-flex justify-content-between mb-2"
                        >

                            <span>

                                Discount

                            </span>


                            <span class="text-success">

                                - ₹<?= number_format(
                                    $order["discount_amount"],
                                    2
                                ) ?>

                            </span>

                        </div>


                        <!-- Shipping -->

                        <div
                            class="d-flex justify-content-between mb-2"
                        >

                            <span>

                                Shipping

                            </span>


                            <span>

                                ₹<?= number_format(
                                    $order["shipping_amount"],
                                    2
                                ) ?>

                            </span>

                        </div>


                        <hr>


                        <!-- Total -->

                        <div
                            class="d-flex justify-content-between"
                        >

                            <strong>

                                Total

                            </strong>


                            <strong>

                                ₹<?= number_format(
                                    $order["total_amount"],
                                    2
                                ) ?>

                            </strong>

                        </div>


                        <hr>


                        <!--
                        |--------------------------------------------------------------------------
                        | Payment Information
                        |--------------------------------------------------------------------------
                        -->

                        <h6 class="fw-bold mt-3">

                            Payment Method

                        </h6>


                        <p>

                            <?= e(
                                strtoupper(
                                    $order["payment_method"]
                                )
                            ) ?>

                        </p>


                        <h6 class="fw-bold">

                            Payment Status

                        </h6>


                        <?php

                        $paymentClass =
                            "text-bg-secondary";


                        if (
                            $order["payment_status"]
                            === "paid"
                        ) {

                            $paymentClass =
                                "text-bg-success";

                        }


                        if (
                            $order["payment_status"]
                            === "failed"
                        ) {

                            $paymentClass =
                                "text-bg-danger";

                        }


                        if (
                            $order["payment_status"]
                            === "refunded"
                        ) {

                            $paymentClass =
                                "text-bg-warning";

                        }

                        ?>


                        <span
                            class="badge <?= $paymentClass ?>"
                        >

                            <?= e(
                                ucfirst(
                                    $order["payment_status"]
                                )
                            ) ?>

                        </span>


                        <!--
                        |--------------------------------------------------------------------------
                        | Order Status
                        |--------------------------------------------------------------------------
                        -->

                        <h6 class="fw-bold mt-4">

                            Order Status

                        </h6>


                        <?php

                        $orderStatusClass =
                            "text-bg-secondary";


                        if (
                            $order["order_status"]
                            === "pending"
                        ) {

                            $orderStatusClass =
                                "text-bg-warning";

                        }


                        if (
                            $order["order_status"]
                            === "processing"
                        ) {

                            $orderStatusClass =
                                "text-bg-info";

                        }


                        if (
                            $order["order_status"]
                            === "shipped"
                        ) {

                            $orderStatusClass =
                                "text-bg-primary";

                        }


                        if (
                            $order["order_status"]
                            === "delivered"
                        ) {

                            $orderStatusClass =
                                "text-bg-success";

                        }


                        if (
                            $order["order_status"]
                            === "cancelled"
                        ) {

                            $orderStatusClass =
                                "text-bg-danger";

                        }

                        ?>


                        <span
                            class="badge <?= $orderStatusClass ?>"
                        >

                            <?= e(
                                ucfirst(
                                    $order["order_status"]
                                )
                            ) ?>

                        </span>


                    </div>

                </div>


            </div>


        </div>


    </div>

</main>


<?php

/*
|--------------------------------------------------------------------------
| Footer
|--------------------------------------------------------------------------
*/

require_once "includes/footer.php";

?>