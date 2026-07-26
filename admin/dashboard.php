<?php

require_once "../config/app.php";


/*
|--------------------------------------------------------------------------
| Require Admin
|--------------------------------------------------------------------------
*/

requireAdmin();


/*
|--------------------------------------------------------------------------
| Dashboard Statistics
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| Total Users
|--------------------------------------------------------------------------
*/

$stmt =
    $pdo->query(
        "SELECT COUNT(*) AS total
         FROM users
         WHERE role = 'customer'"
    );


$totalCustomers =
    $stmt->fetch()["total"];


/*
|--------------------------------------------------------------------------
| Total Vendors
|--------------------------------------------------------------------------
*/

$stmt =
    $pdo->query(
        "SELECT COUNT(*) AS total
         FROM vendors
         WHERE status = 'approved'"
    );


$totalVendors =
    $stmt->fetch()["total"];


/*
|--------------------------------------------------------------------------
| Total Products
|--------------------------------------------------------------------------
*/

$stmt =
    $pdo->query(
        "SELECT COUNT(*) AS total
         FROM products
         WHERE status = 'active'"
    );


$totalProducts =
    $stmt->fetch()["total"];


/*
|--------------------------------------------------------------------------
| Total Orders
|--------------------------------------------------------------------------
*/

$stmt =
    $pdo->query(
        "SELECT COUNT(*) AS total
         FROM orders"
    );


$totalOrders =
    $stmt->fetch()["total"];


/*
|--------------------------------------------------------------------------
| Total Sales
|--------------------------------------------------------------------------
*/

$stmt =
    $pdo->query(
        "SELECT
            COALESCE(
                SUM(total_amount),
                0
            ) AS total

         FROM orders

         WHERE payment_status = 'paid'

         OR payment_method = 'cod'

         AND order_status != 'cancelled'"
    );


$totalSales =
    $stmt->fetch()["total"];


/*
|--------------------------------------------------------------------------
| Recent Orders
|--------------------------------------------------------------------------
*/

$stmt =
    $pdo->query(
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


$pageTitle =
    "Admin Dashboard | "
    . APP_NAME;


require_once "includes/header.php";

?>


<main>

    <div class="container-fluid py-4">


        <div class="d-flex justify-content-between align-items-center mb-4">


            <div>

                <h1 class="fw-bold">

                    Dashboard

                </h1>


                <p class="text-muted">

                    Welcome back,

                    <?= e(
                        $_SESSION["user_name"]
                    ) ?>

                </p>

            </div>


        </div>


        <!-- Statistics -->


        <div class="row g-4 mb-4">


            <!-- Customers -->

            <div class="col-md-6 col-xl-3">


                <div class="card border-0 shadow-sm h-100">


                    <div class="card-body">


                        <div class="d-flex justify-content-between">


                            <div>

                                <p class="text-muted mb-1">

                                    Customers

                                </p>


                                <h2 class="fw-bold">

                                    <?= e(
                                        $totalCustomers
                                    ) ?>

                                </h2>

                            </div>


                            <i
                                class="bi bi-people"
                                style="font-size: 35px;"
                            ></i>


                        </div>


                    </div>

                </div>


            </div>


            <!-- Vendors -->

            <div class="col-md-6 col-xl-3">


                <div class="card border-0 shadow-sm h-100">


                    <div class="card-body">


                        <div class="d-flex justify-content-between">


                            <div>

                                <p class="text-muted mb-1">

                                    Vendors

                                </p>


                                <h2 class="fw-bold">

                                    <?= e(
                                        $totalVendors
                                    ) ?>

                                </h2>

                            </div>


                            <i
                                class="bi bi-shop"
                                style="font-size: 35px;"
                            ></i>


                        </div>


                    </div>

                </div>


            </div>


            <!-- Products -->

            <div class="col-md-6 col-xl-3">


                <div class="card border-0 shadow-sm h-100">


                    <div class="card-body">


                        <div class="d-flex justify-content-between">


                            <div>

                                <p class="text-muted mb-1">

                                    Products

                                </p>


                                <h2 class="fw-bold">

                                    <?= e(
                                        $totalProducts
                                    ) ?>

                                </h2>

                            </div>


                            <i
                                class="bi bi-box-seam"
                                style="font-size: 35px;"
                            ></i>


                        </div>


                    </div>

                </div>


            </div>


            <!-- Orders -->

            <div class="col-md-6 col-xl-3">


                <div class="card border-0 shadow-sm h-100">


                    <div class="card-body>


                        <div class="d-flex justify-content-between">


                            <div>

                                <p class="text-muted mb-1">

                                    Orders

                                </p>


                                <h2 class="fw-bold">

                                    <?= e(
                                        $totalOrders
                                    ) ?>

                                </h2>

                            </div>


                            <i
                                class="bi bi-cart-check"
                                style="font-size: 35px;"
                            ></i>


                        </div>


                    </div>

                </div>


            </div>


        </div>


        <!-- Sales -->


        <div class="row mb-4">


            <div class="col-lg-6">


                <div class="card border-0 shadow-sm">


                    <div class="card-body">


                        <p class="text-muted mb-1">

                            Total Sales

                        </p>


                        <h2 class="fw-bold">

                            ₹<?= number_format(
                                $totalSales,
                                2
                            ) ?>

                        </h2>


                    </div>

                </div>


            </div>


        </div>


        <!-- Recent Orders -->


        <div class="card border-0 shadow-sm">


            <div class="card-body">


                <h4 class="fw-bold mb-4">

                    Recent Orders

                </h4>


                <div
                    class="table-responsive"
                >


                    <table
                        class="table align-middle"
                    >


                        <thead>

                            <tr>

                                <th>
                                    Order
                                </th>

                                <th>
                                    Customer
                                </th>

                                <th>
                                    Total
                                </th>

                                <th>
                                    Payment
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Date
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php if (
                                empty(
                                    $recentOrders
                                )
                            ): ?>


                                <tr>

                                    <td
                                        colspan="6"
                                        class="text-center text-muted"
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

                                            #<?= e(
                                                $order["id"]
                                            ) ?>

                                        </td>


                                        <td>

                                            <?= e(
                                                $order["customer_name"]
                                            ) ?>

                                        </td>


                                        <td>

                                            ₹<?= number_format(
                                                $order["total_amount"],
                                                2
                                            ) ?>

                                        </td>


                                        <td>

                                            <?= e(
                                                ucfirst(
                                                    $order["payment_status"]
                                                )
                                            ) ?>

                                        </td>


                                        <td>

                                            <?= e(
                                                ucfirst(
                                                    $order["order_status"]
                                                )
                                            ) ?>

                                        </td>


                                        <td>

                                            <?= date(
                                                "d M Y",
                                                strtotime(
                                                    $order["created_at"]
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


    </div>

</main>


<?php

require_once "includes/footer.php";

?>