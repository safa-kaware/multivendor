<?php

require_once "../config/app.php";

requireAdmin();


/*
|--------------------------------------------------------------------------
| GET ORDER ID
|--------------------------------------------------------------------------
*/

$orderId = isset($_GET["id"])
    ? (int) $_GET["id"]
    : 0;


if ($orderId <= 0) {

    http_response_code(404);

    die("Invalid order ID.");

}


/*
|--------------------------------------------------------------------------
| FETCH ORDER
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(

    "SELECT
        o.*,

        u.name AS customer_name,
        u.email AS customer_email

     FROM orders o

     INNER JOIN users u
        ON u.id = o.user_id

     WHERE o.id = ?

     LIMIT 1"

);

$stmt->execute([

    $orderId

]);

$order =
    $stmt->fetch();


if (!$order) {

    http_response_code(404);

    die("Order not found.");

}


/*
|--------------------------------------------------------------------------
| FETCH ORDER ITEMS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(

    "SELECT
        oi.*,

        p.name AS product_name,

        v.store_name

     FROM order_items oi

     INNER JOIN products p
        ON p.id = oi.product_id

     LEFT JOIN vendors v
        ON v.id = p.vendor_id

     WHERE oi.order_id = ?

     ORDER BY oi.id ASC"

);

$stmt->execute([

    $orderId

]);

$orderItems =
    $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| UPDATE ORDER STATUS
|--------------------------------------------------------------------------
*/

$allowedStatuses = [

    "pending",

    "confirmed",

    "processing",

    "shipped",

    "delivered",

    "cancelled"

];


if (
    $_SERVER["REQUEST_METHOD"]
    === "POST"

    &&
    isset(
        $_POST["order_status"]
    )
) {

    $newStatus =
        $_POST[
            "order_status"
        ];


    if (
        in_array(
            $newStatus,
            $allowedStatuses,
            true
        )
    ) {

        $stmt = $pdo->prepare(

            "UPDATE orders

             SET order_status = ?

             WHERE id = ?"

        );

        $stmt->execute([

            $newStatus,

            $orderId

        ]);


        redirect(

            BASE_URL
            . "admin/order-view.php?id="
            . $orderId

        );

    }

}


$pageTitle =
    "Order #"
    . $orderId
    . " | "
    . APP_NAME;


require_once "includes/header.php";

?>


<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h1 class="fw-bold">

            Order #<?= $orderId ?>

        </h1>

        <p class="text-muted mb-0">

            Order details and status management.

        </p>

    </div>


    <a
        href="<?= BASE_URL ?>admin/orders.php"
        class="btn btn-outline-secondary"
    >

        <i class="bi bi-arrow-left"></i>

        Back to Orders

    </a>

</div>


<div class="row g-4">


    <!-- ORDER INFORMATION -->


    <div class="col-lg-8">


        <div class="card border-0 shadow-sm mb-4">


            <div class="card-header bg-white">

                <h5 class="mb-0 fw-bold">

                    Order Items

                </h5>

            </div>


            <div class="card-body">


                <?php if (
                    empty($orderItems)
                ): ?>


                    <p class="text-muted mb-0">

                        No order items found.

                    </p>


                <?php else: ?>


                    <div class="table-responsive">


                        <table
                            class="table align-middle"
                        >


                            <thead>

                                <tr>

                                    <th>Product</th>

                                    <th>Vendor</th>

                                    <th>Price</th>

                                    <th>Quantity</th>

                                    <th>Subtotal</th>

                                </tr>

                            </thead>


                            <tbody>


                            <?php foreach (
                                $orderItems
                                as $item
                            ): ?>


                                <tr>


                                    <td>

                                        <strong>

                                            <?= e(
                                                $item[
                                                    "product_name"
                                                ]
                                            ) ?>

                                        </strong>

                                    </td>


                                    <td>

                                        <?= e(
                                            $item[
                                                "store_name"
                                            ]
                                            ??
                                            "N/A"
                                        ) ?>

                                    </td>


                                    <td>

                                        ₹<?= number_format(
                                            (float)
                                            $item[
                                                "price"
                                            ],
                                            2
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= (int)
                                            $item[
                                                "quantity"
                                            ] ?>

                                    </td>


                                    <td>

                                        ₹<?= number_format(
                                            (
                                                (float)
                                                $item[
                                                    "price"
                                                ]
                                                *
                                                (int)
                                                $item[
                                                    "quantity"
                                                ]
                                            ),
                                            2
                                        ) ?>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                            </tbody>


                        </table>


                    </div>


                <?php endif; ?>


            </div>


        </div>


    </div>



    <!-- ORDER SUMMARY -->


    <div class="col-lg-4">


        <div class="card border-0 shadow-sm mb-4">


            <div class="card-header bg-white">

                <h5 class="mb-0 fw-bold">

                    Order Summary

                </h5>

            </div>


            <div class="card-body">


                <p>

                    <strong>
                        Order ID:
                    </strong>

                    #<?= (int)
                        $order["id"]
                    ?>

                </p>


                <p>

                    <strong>
                        Customer:
                    </strong>

                    <?= e(
                        $order[
                            "customer_name"
                        ]
                    ) ?>

                </p>


                <p>

                    <strong>
                        Email:
                    </strong>

                    <?= e(
                        $order[
                            "customer_email"
                        ]
                    ) ?>

                </p>


                <p>

                    <strong>
                        Payment Method:
                    </strong>

                    <?= e(
                        ucfirst(
                            $order[
                                "payment_method"
                            ]
                        )
                    ) ?>

                </p>


                <p>

                    <strong>
                        Payment Status:
                    </strong>

                    <?= e(
                        ucfirst(
                            $order[
                                "payment_status"
                            ]
                        )
                    ) ?>

                </p>


                <hr>


                <h4 class="fw-bold">

                    Total:

                    ₹<?= number_format(
                        (float)
                        $order[
                            "total_amount"
                        ],
                        2
                    ) ?>

                </h4>


            </div>


        </div>



        <!-- STATUS UPDATE -->


        <div class="card border-0 shadow-sm">


            <div class="card-header bg-white">

                <h5 class="mb-0 fw-bold">

                    Update Order Status

                </h5>

            </div>


            <div class="card-body">


                <form
                    method="POST"
                >


                    <div class="mb-3">


                        <label
                            class="form-label fw-bold"
                        >

                            Order Status

                        </label>


                        <select
                            name="order_status"
                            class="form-select"
                            required
                        >


                            <?php foreach (
                                $allowedStatuses
                                as $status
                            ): ?>


                                <option
                                    value="<?= e(
                                        $status
                                    ) ?>"
                                    <?= $order[
                                        "order_status"
                                    ]
                                    === $status
                                        ? "selected"
                                        : ""
                                    ?>
                                >

                                    <?= e(
                                        ucfirst(
                                            $status
                                        )
                                    ) ?>

                                </option>


                            <?php endforeach; ?>


                        </select>


                    </div>


                    <button
                        type="submit"
                        class="btn btn-primary w-100"
                    >

                        Update Status

                    </button>


                </form>


            </div>


        </div>


    </div>


</div>


<?php require_once "includes/footer.php"; ?>