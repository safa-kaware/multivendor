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
| FETCH CUSTOMER ORDERS
|--------------------------------------------------------------------------
*/

$orders = getUserOrders(
    $pdo,
    $userId
);


/*
|--------------------------------------------------------------------------
| PAGE
|--------------------------------------------------------------------------
*/

$pageTitle =
    "My Orders | "
    . APP_NAME;


require_once "includes/header.php";

?>


<div class="container py-5">


    <!-- PAGE HEADER -->

    <div class="mb-4">

        <h1 class="fw-bold">

            My Orders

        </h1>

        <p class="text-muted mb-0">

            View and track all your orders.

        </p>

    </div>



    <?php if (empty($orders)): ?>


        <!-- NO ORDERS -->

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


                <h3 class="fw-bold">

                    No Orders Yet

                </h3>


                <p class="text-muted">

                    You haven't placed any orders yet.

                </p>


                <a
                    href="<?= BASE_URL ?>index.php"
                    class="btn btn-primary"
                >

                    Start Shopping

                </a>

            </div>

        </div>


    <?php else: ?>


        <!-- ORDERS TABLE -->

        <div
            class="card border-0 shadow-sm"
        >

            <div
                class="card-body"
            >

                <div
                    class="table-responsive"
                >

                    <table
                        class="table table-hover align-middle"
                    >

                        <thead>

                            <tr>

                                <th>
                                    Order #
                                </th>

                                <th>
                                    Date
                                </th>

                                <th>
                                    Total
                                </th>

                                <th>
                                    Payment
                                </th>

                                <th>
                                    Payment Status
                                </th>

                                <th>
                                    Order Status
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>


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



                                    <!-- DATE -->

                                    <td>

                                        <?= date(
                                            "d M Y",
                                            strtotime(
                                                $order["created_at"]
                                            )
                                        ) ?>

                                        <br>

                                        <small
                                            class="text-muted"
                                        >

                                            <?= date(
                                                "h:i A",
                                                strtotime(
                                                    $order["created_at"]
                                                )
                                            ) ?>

                                        </small>

                                    </td>



                                    <!-- TOTAL -->

                                    <td>

                                        <strong>

                                            ₹<?= number_format(
                                                (float)
                                                $order[
                                                    "total_amount"
                                                ],
                                                2
                                            ) ?>

                                        </strong>

                                    </td>



                                    <!-- PAYMENT METHOD -->

                                    <td>

                                        <?php if (
                                            $order[
                                                "payment_method"
                                            ]
                                            === "cod"
                                        ): ?>

                                            <span>

                                                Cash on Delivery

                                            </span>

                                        <?php elseif (
                                            $order[
                                                "payment_method"
                                            ]
                                            === "stripe"
                                        ): ?>

                                            <span>

                                                Stripe

                                            </span>

                                        <?php elseif (
                                            $order[
                                                "payment_method"
                                            ]
                                            === "razorpay"
                                        ): ?>

                                            <span>

                                                Razorpay

                                            </span>

                                        <?php else: ?>

                                            <span>

                                                <?= e(
                                                    $order[
                                                        "payment_method"
                                                    ]
                                                ) ?>

                                            </span>

                                        <?php endif; ?>

                                    </td>



                                    <!-- PAYMENT STATUS -->

                                    <td>

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

                                        <?php if (
                                            $order[
                                                "order_status"
                                            ]
                                            === "pending"
                                        ): ?>

                                            <span
                                                class="badge text-bg-warning"
                                            >

                                                Pending

                                            </span>


                                        <?php elseif (
                                            $order[
                                                "order_status"
                                            ]
                                            === "confirmed"
                                        ): ?>

                                            <span
                                                class="badge text-bg-primary"
                                            >

                                                Confirmed

                                            </span>


                                        <?php elseif (
                                            $order[
                                                "order_status"
                                            ]
                                            === "processing"
                                        ): ?>

                                            <span
                                                class="badge text-bg-info"
                                            >

                                                Processing

                                            </span>


                                        <?php elseif (
                                            $order[
                                                "order_status"
                                            ]
                                            === "shipped"
                                        ): ?>

                                            <span
                                                class="badge text-bg-primary"
                                            >

                                                Shipped

                                            </span>


                                        <?php elseif (
                                            $order[
                                                "order_status"
                                            ]
                                            === "delivered"
                                        ): ?>

                                            <span
                                                class="badge text-bg-success"
                                            >

                                                Delivered

                                            </span>


                                        <?php elseif (
                                            $order[
                                                "order_status"
                                            ]
                                            === "cancelled"
                                        ): ?>

                                            <span
                                                class="badge text-bg-danger"
                                            >

                                                Cancelled

                                            </span>


                                        <?php else: ?>

                                            <span
                                                class="badge text-bg-secondary"
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

                                    </td>



                                    <!-- VIEW ORDER -->

                                    <td>

                                        <a
                                            href="<?= BASE_URL ?>order-view.php?id=<?= (int)
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


                        </tbody>

                    </table>

                </div>

            </div>

        </div>


    <?php endif; ?>


</div>


<?php

require_once "includes/footer.php";

?>