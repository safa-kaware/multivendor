<?php

require_once "../config/app.php";

requireAdmin();

$pageTitle = "Manage Orders | " . APP_NAME;


/*
|--------------------------------------------------------------------------
| FETCH ORDERS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query(
    "SELECT
        o.id,
        o.user_id,
        o.total_amount,
        o.payment_method,
        o.payment_status,
        o.order_status,
        o.created_at,

        u.name AS customer_name,
        u.email AS customer_email

     FROM orders o

     INNER JOIN users u
        ON u.id = o.user_id

     ORDER BY o.created_at DESC"
);

$orders = $stmt->fetchAll();

?>


<?php require_once "includes/header.php"; ?>


<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h1 class="fw-bold">
            Orders
        </h1>

        <p class="text-muted mb-0">
            Manage and monitor all customer orders.
        </p>

    </div>

</div>


<div class="card border-0 shadow-sm">

    <div class="card-body">

        <div class="table-responsive">

            <table class="table table-hover align-middle">

                <thead>

                    <tr>

                        <th>Order ID</th>

                        <th>Customer</th>

                        <th>Total</th>

                        <th>Payment</th>

                        <th>Order Status</th>

                        <th>Date</th>

                        <th>Action</th>

                    </tr>

                </thead>


                <tbody>


                <?php if (empty($orders)): ?>


                    <tr>

                        <td
                            colspan="7"
                            class="text-center py-5 text-muted"
                        >

                            No orders found.

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
                                        $order["id"]
                                    ?>

                                </strong>

                            </td>


                            <!-- CUSTOMER -->


                            <td>

                                <strong>

                                    <?= e(
                                        $order["customer_name"]
                                    ) ?>

                                </strong>

                                <small
                                    class="d-block text-muted"
                                >

                                    <?= e(
                                        $order["customer_email"]
                                    ) ?>

                                </small>

                            </td>


                            <!-- TOTAL -->


                            <td>

                                <strong>

                                    ₹<?= number_format(
                                        (float)
                                        $order["total_amount"],
                                        2
                                    ) ?>

                                </strong>

                            </td>


                            <!-- PAYMENT -->


                            <td>

                                <span
                                    class="badge text-bg-secondary"
                                >

                                    <?= e(
                                        ucfirst(
                                            $order["payment_method"]
                                        )
                                    ) ?>

                                </span>


                                <small
                                    class="d-block mt-1"
                                >

                                    <?php if (
                                        $order["payment_status"]
                                        === "paid"
                                    ): ?>

                                        <span
                                            class="text-success"
                                        >

                                            Paid

                                        </span>

                                    <?php elseif (
                                        $order["payment_status"]
                                        === "pending"
                                    ): ?>

                                        <span
                                            class="text-warning"
                                        >

                                            Pending

                                        </span>

                                    <?php else: ?>

                                        <span
                                            class="text-danger"
                                        >

                                            <?= e(
                                                ucfirst(
                                                    $order[
                                                        "payment_status"
                                                    ]
                                                )
                                            ) ?>

                                        </span>

                                    <?php endif; ?>

                                </small>

                            </td>


                            <!-- ORDER STATUS -->


                            <td>


                                <?php

                                $status =
                                    $order[
                                        "order_status"
                                    ];

                                $statusClass =
                                    "secondary";

                                if (
                                    $status
                                    === "pending"
                                ) {

                                    $statusClass =
                                        "warning";

                                } elseif (
                                    $status
                                    === "confirmed"
                                ) {

                                    $statusClass =
                                        "info";

                                } elseif (
                                    $status
                                    === "processing"
                                ) {

                                    $statusClass =
                                        "primary";

                                } elseif (
                                    $status
                                    === "shipped"
                                ) {

                                    $statusClass =
                                        "dark";

                                } elseif (
                                    $status
                                    === "delivered"
                                ) {

                                    $statusClass =
                                        "success";

                                } elseif (
                                    $status
                                    === "cancelled"
                                ) {

                                    $statusClass =
                                        "danger";

                                }

                                ?>


                                <span
                                    class="badge text-bg-<?= $statusClass ?>"
                                >

                                    <?= e(
                                        ucfirst(
                                            $status
                                        )
                                    ) ?>

                                </span>


                            </td>


                            <!-- DATE -->


                            <td>

                                <?= date(
                                    "d M Y, h:i A",
                                    strtotime(
                                        $order[
                                            "created_at"
                                        ]
                                    )
                                ) ?>

                            </td>


                            <!-- ACTION -->


                            <td>

                                <a
                                    href="<?= BASE_URL ?>admin/order-view.php?id=<?= (int) $order["id"] ?>"
                                    class="btn btn-sm btn-outline-primary"
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


<?php require_once "includes/footer.php"; ?>