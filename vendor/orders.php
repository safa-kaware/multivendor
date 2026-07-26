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
| FETCH VENDOR ORDERS
|--------------------------------------------------------------------------
|
| We fetch orders that contain at least one
| product belonging to the current vendor.
|
| DISTINCT prevents duplicate orders when
| one order contains multiple products from
| the same vendor.
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
        a.city AS delivery_city,
        a.state AS delivery_state,
        a.pincode AS delivery_pincode

     FROM orders o

     INNER JOIN users u
        ON u.id = o.user_id

     INNER JOIN addresses a
        ON a.id = o.address_id

     INNER JOIN order_items oi
        ON oi.order_id = o.id

     WHERE oi.vendor_id = ?

     ORDER BY o.created_at DESC"

);


$stmt->execute([

    $vendorId

]);


$orders =
    $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| PAGE TITLE
|--------------------------------------------------------------------------
*/

$pageTitle =
    "Vendor Orders | "
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

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h1 class="fw-bold">

                Orders

            </h1>

            <p class="text-muted mb-0">

                Manage orders containing your products.

            </p>

        </div>


        <div>

            <span class="badge text-bg-dark fs-6">

                <?= count($orders) ?>

                Order(s)

            </span>

        </div>

    </div>



    <!-- ORDERS TABLE -->

    <div class="card border-0 shadow-sm">

        <div class="card-body">


            <div class="table-responsive">


                <table class="table table-hover align-middle">


                    <thead>

                        <tr>

                            <th>
                                Order ID
                            </th>

                            <th>
                                Customer
                            </th>

                            <th>
                                Delivery Location
                            </th>

                            <th>
                                Payment
                            </th>

                            <th>
                                Order Status
                            </th>

                            <th>
                                Date
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <?php if (
                            empty($orders)
                        ): ?>


                            <tr>

                                <td
                                    colspan="7"
                                    class="text-center py-5"
                                >

                                    <div
                                        class="display-5 mb-3"
                                    >

                                        📦

                                    </div>


                                    <h5>

                                        No orders yet

                                    </h5>


                                    <p class="text-muted mb-0">

                                        Orders containing your products
                                        will appear here.

                                    </p>

                                </td>

                            </tr>


                        <?php else: ?>


                            <?php foreach (
                                $orders
                                as $order
                            ): ?>


                                <tr>


                                    <!-- ORDER ID -->

                                    <td>

                                        <strong>

                                            #<?= (int)
                                                $order["id"] ?>

                                        </strong>

                                    </td>



                                    <!-- CUSTOMER -->

                                    <td>

                                        <strong>

                                            <?= e(
                                                $order["customer_name"]
                                            ) ?>

                                        </strong>


                                        <br>


                                        <small
                                            class="text-muted"
                                        >

                                            <?= e(
                                                $order["customer_email"]
                                            ) ?>

                                        </small>

                                    </td>



                                    <!-- DELIVERY -->

                                    <td>

                                        <?= e(
                                            $order["delivery_city"]
                                        ) ?>


                                        <br>


                                        <small
                                            class="text-muted"
                                        >

                                            <?= e(
                                                $order["delivery_state"]
                                            ) ?>

                                            -

                                            <?= e(
                                                $order["delivery_pincode"]
                                            ) ?>

                                        </small>

                                    </td>



                                    <!-- PAYMENT -->

                                    <td>


                                        <strong>

                                            <?= e(
                                                strtoupper(
                                                    $order[
                                                        "payment_method"
                                                    ]
                                                )
                                            ) ?>

                                        </strong>


                                        <br>


                                        <?php if (
                                            $order[
                                                "payment_status"
                                            ]
                                            === "paid"
                                        ): ?>


                                            <span
                                                class="badge text-bg-success"
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
                                                class="badge text-bg-danger"
                                            >

                                                Failed

                                            </span>


                                        <?php else: ?>


                                            <span
                                                class="badge text-bg-warning"
                                            >

                                                Pending

                                            </span>


                                        <?php endif; ?>


                                    </td>



                                    <!-- ORDER STATUS -->

                                    <td>


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
                                                class="badge text-bg-warning"
                                            >

                                                Pending

                                            </span>


                                        <?php elseif (
                                            $status
                                            === "confirmed"
                                        ): ?>


                                            <span
                                                class="badge text-bg-info"
                                            >

                                                Confirmed

                                            </span>


                                        <?php elseif (
                                            $status
                                            === "processing"
                                        ): ?>


                                            <span
                                                class="badge text-bg-primary"
                                            >

                                                Processing

                                            </span>


                                        <?php elseif (
                                            $status
                                            === "shipped"
                                        ): ?>


                                            <span
                                                class="badge text-bg-secondary"
                                            >

                                                Shipped

                                            </span>


                                        <?php elseif (
                                            $status
                                            === "delivered"
                                        ): ?>


                                            <span
                                                class="badge text-bg-success"
                                            >

                                                Delivered

                                            </span>


                                        <?php elseif (
                                            $status
                                            === "cancelled"
                                        ): ?>


                                            <span
                                                class="badge text-bg-danger"
                                            >

                                                Cancelled

                                            </span>


                                        <?php elseif (
                                            $status
                                            === "refunded"
                                        ): ?>


                                            <span
                                                class="badge text-bg-dark"
                                            >

                                                Refunded

                                            </span>


                                        <?php else: ?>


                                            <span
                                                class="badge text-bg-secondary"
                                            >

                                                <?= e(
                                                    ucfirst(
                                                        $status
                                                    )
                                                ) ?>

                                            </span>


                                        <?php endif; ?>


                                    </td>



                                    <!-- DATE -->

                                    <td>

                                        <?= date(
                                            "d M Y",
                                            strtotime(
                                                $order[
                                                    "created_at"
                                                ]
                                            )
                                        ) ?>


                                        <br>


                                        <small
                                            class="text-muted"
                                        >

                                            <?= date(
                                                "h:i A",
                                                strtotime(
                                                    $order[
                                                        "created_at"
                                                    ]
                                                )
                                            ) ?>

                                        </small>

                                    </td>



                                    <!-- ACTION -->

                                    <td>

                                        <a
                                            href="<?= BASE_URL ?>vendor/order-view.php?id=<?= (int)
                                                $order["id"] ?>"
                                            class="btn btn-sm btn-outline-dark"
                                        >

                                            <i
                                                class="bi bi-eye"
                                            ></i>

                                            View

                                        </a>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        <?php endif; ?>


                    </tbody>


                </table>


            </div>


        </div>

    </div>


</div>


<?php

require_once "../includes/footer.php";

?>