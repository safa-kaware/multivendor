<?php

require_once "../config/app.php";

requireAdmin();

$pageTitle = "Admin Dashboard | " . APP_NAME;


/*
|--------------------------------------------------------------------------
| TOTAL USERS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query(
    "SELECT COUNT(*) AS total
     FROM users"
);

$totalUsers =
    (int) $stmt->fetch()["total"];


/*
|--------------------------------------------------------------------------
| TOTAL VENDORS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query(
    "SELECT COUNT(*) AS total
     FROM vendors"
);

$totalVendors =
    (int) $stmt->fetch()["total"];


/*
|--------------------------------------------------------------------------
| APPROVED VENDORS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query(
    "SELECT COUNT(*) AS total
     FROM vendors
     WHERE status = 'approved'"
);

$approvedVendors =
    (int) $stmt->fetch()["total"];


/*
|--------------------------------------------------------------------------
| TOTAL PRODUCTS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query(
    "SELECT COUNT(*) AS total
     FROM products"
);

$totalProducts =
    (int) $stmt->fetch()["total"];


/*
|--------------------------------------------------------------------------
| ACTIVE PRODUCTS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query(
    "SELECT COUNT(*) AS total
     FROM products
     WHERE status = 'active'"
);

$activeProducts =
    (int) $stmt->fetch()["total"];


/*
|--------------------------------------------------------------------------
| TOTAL ORDERS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query(
    "SELECT COUNT(*) AS total
     FROM orders"
);

$totalOrders =
    (int) $stmt->fetch()["total"];


/*
|--------------------------------------------------------------------------
| PENDING ORDERS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query(
    "SELECT COUNT(*) AS total
     FROM orders
     WHERE order_status = 'pending'"
);

$pendingOrders =
    (int) $stmt->fetch()["total"];


/*
|--------------------------------------------------------------------------
| TOTAL REVENUE
|--------------------------------------------------------------------------
|
| Count only paid orders.
|
*/

$stmt = $pdo->query(
    "SELECT
        COALESCE(
            SUM(total_amount),
            0
        ) AS total

     FROM orders

     WHERE payment_status = 'paid'

     AND order_status != 'cancelled'"
);

$totalRevenue =
    (float) $stmt->fetch()["total"];


/*
|--------------------------------------------------------------------------
| RECENT ORDERS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query(
    "SELECT
        o.id,
        o.total_amount,
        o.payment_status,
        o.order_status,
        o.created_at,

        u.name AS customer_name

     FROM orders o

     INNER JOIN users u
        ON u.id = o.user_id

     ORDER BY o.created_at DESC

     LIMIT 5"
);

$recentOrders =
    $stmt->fetchAll();


require_once "includes/header.php";

?>


<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h1 class="fw-bold">
            Admin Dashboard
        </h1>

        <p class="text-muted mb-0">
            Overview of your marketplace.
        </p>

    </div>

</div>


<!-- STATISTICS -->


<div class="row g-4 mb-5">


    <!-- USERS -->


    <div class="col-md-6 col-xl-3">

        <div class="card border-0 shadow-sm h-100">

            <div class="card-body">

                <div
                    class="d-flex justify-content-between align-items-center"
                >

                    <div>

                        <p class="text-muted mb-1">
                            Total Users
                        </p>

                        <h2 class="fw-bold mb-0">

                            <?= $totalUsers ?>

                        </h2>

                    </div>

                    <i
                        class="bi bi-people fs-1 text-primary"
                    ></i>

                </div>

            </div>

        </div>

    </div>


    <!-- VENDORS -->


    <div class="col-md-6 col-xl-3">

        <div class="card border-0 shadow-sm h-100">

            <div class="card-body">

                <div
                    class="d-flex justify-content-between align-items-center"
                >

                    <div>

                        <p class="text-muted mb-1">
                            Total Vendors
                        </p>

                        <h2 class="fw-bold mb-0">

                            <?= $totalVendors ?>

                        </h2>

                        <small class="text-success">

                            <?= $approvedVendors ?>
                            approved

                        </small>

                    </div>

                    <i
                        class="bi bi-shop fs-1 text-success"
                    ></i>

                </div>

            </div>

        </div>

    </div>


    <!-- PRODUCTS -->


    <div class="col-md-6 col-xl-3">

        <div class="card border-0 shadow-sm h-100">

            <div class="card-body">

                <div
                    class="d-flex justify-content-between align-items-center"
                >

                    <div>

                        <p class="text-muted mb-1">
                            Products
                        </p>

                        <h2 class="fw-bold mb-0">

                            <?= $totalProducts ?>

                        </h2>

                        <small class="text-success">

                            <?= $activeProducts ?>
                            active

                        </small>

                    </div>

                    <i
                        class="bi bi-box-seam fs-1 text-warning"
                    ></i>

                </div>

            </div>

        </div>

    </div>


    <!-- ORDERS -->


    <div class="col-md-6 col-xl-3">

        <div class="card border-0 shadow-sm h-100">

            <div class="card-body">

                <div
                    class="d-flex justify-content-between align-items-center"
                >

                    <div>

                        <p class="text-muted mb-1">
                            Total Orders
                        </p>

                        <h2 class="fw-bold mb-0">

                            <?= $totalOrders ?>

                        </h2>

                        <small class="text-warning">

                            <?= $pendingOrders ?>
                            pending

                        </small>

                    </div>

                    <i
                        class="bi bi-cart-check fs-1 text-info"
                    ></i>

                </div>

            </div>

        </div>

    </div>


</div>


<!-- REVENUE -->


<div class="row g-4 mb-5">


    <div class="col-lg-6">

        <div class="card border-0 shadow-sm">

            <div class="card-body">

                <div
                    class="d-flex justify-content-between align-items-center"
                >

                    <div>

                        <p class="text-muted mb-1">

                            Total Revenue

                        </p>

                        <h2 class="fw-bold mb-0">

                            ₹<?= number_format(
                                $totalRevenue,
                                2
                            ) ?>

                        </h2>

                        <small class="text-muted">

                            From paid orders

                        </small>

                    </div>

                    <i
                        class="bi bi-currency-rupee fs-1 text-success"
                    ></i>

                </div>

            </div>

        </div>

    </div>


    <div class="col-lg-6">

        <div class="card border-0 shadow-sm">

            <div class="card-body">

                <h5 class="fw-bold mb-3">

                    Quick Actions

                </h5>


                <div class="d-flex gap-2 flex-wrap">


                    <a
                        href="<?= BASE_URL ?>admin/orders.php"
                        class="btn btn-outline-primary"
                    >

                        <i class="bi bi-cart"></i>

                        Orders

                    </a>


                    <a
                        href="<?= BASE_URL ?>admin/vendors.php"
                        class="btn btn-outline-success"
                    >

                        <i class="bi bi-shop"></i>

                        Vendors

                    </a>


                    <a
                        href="<?= BASE_URL ?>admin/products.php"
                        class="btn btn-outline-warning"
                    >

                        <i class="bi bi-box"></i>

                        Products

                    </a>


                    <a
                        href="<?= BASE_URL ?>admin/users.php"
                        class="btn btn-outline-dark"
                    >

                        <i class="bi bi-people"></i>

                        Users

                    </a>


                </div>

            </div>

        </div>

    </div>


</div>


<!-- RECENT ORDERS -->


<div class="card border-0 shadow-sm">


    <div
        class="card-header bg-white d-flex justify-content-between align-items-center"
    >

        <h5 class="fw-bold mb-0">

            Recent Orders

        </h5>


        <a
            href="<?= BASE_URL ?>admin/orders.php"
            class="btn btn-sm btn-outline-primary"
        >

            View All

        </a>

    </div>


    <div class="card-body">


        <div class="table-responsive">


            <table class="table table-hover align-middle mb-0">


                <thead>

                    <tr>

                        <th>Order</th>

                        <th>Customer</th>

                        <th>Total</th>

                        <th>Payment</th>

                        <th>Status</th>

                        <th>Date</th>

                    </tr>

                </thead>


                <tbody>


                <?php if (
                    empty($recentOrders)
                ): ?>


                    <tr>

                        <td
                            colspan="6"
                            class="text-center py-4 text-muted"
                        >

                            No orders found.

                        </td>

                    </tr>


                <?php else: ?>


                    <?php foreach (
                        $recentOrders
                        as $order
                    ): ?>


                        <tr>


                            <td>

                                <strong>

                                    #<?= (int)
                                        $order["id"]
                                    ?>

                                </strong>

                            </td>


                            <td>

                                <?= e(
                                    $order[
                                        "customer_name"
                                    ]
                                ) ?>

                            </td>


                            <td>

                                ₹<?= number_format(
                                    (float)
                                    $order[
                                        "total_amount"
                                    ],
                                    2
                                ) ?>

                            </td>


                            <td>

                                <?= e(
                                    ucfirst(
                                        $order[
                                            "payment_status"
                                        ]
                                    )
                                ) ?>

                            </td>


                            <td>

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

                            </td>


                            <td>

                                <?= date(
                                    "d M Y",
                                    strtotime(
                                        $order[
                                            "created_at"
                                        ]
                                    )
                                ) ?>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                <?php endif; ?>


                </tbody>


            </table>


        </div>


    </div>


</div>


<?php

require_once "includes/footer.php";

?>