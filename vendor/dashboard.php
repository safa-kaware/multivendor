<?php

require_once "../config/app.php";

requireVendor($pdo);

$vendorId = currentVendorId($pdo);

/*
|--------------------------------------------------------------------------
| Get Vendor ID
|--------------------------------------------------------------------------
*/

$vendorId =
    currentVendorId($pdo);


if (
    !$vendorId
) {

    redirect(
        BASE_URL
        . "login.php"
    );

}


/*
|--------------------------------------------------------------------------
| Vendor Information
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT
        store_name,
        description,
        logo,
        status

     FROM vendors

     WHERE id = ?

     LIMIT 1"
);

$stmt->execute([
    $vendorId
]);

$vendor =
    $stmt->fetch();


/*
|--------------------------------------------------------------------------
| Product Count
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT COUNT(*)

     FROM products

     WHERE vendor_id = ?"
);

$stmt->execute([
    $vendorId
]);

$productCount =
    (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| Active Products
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT COUNT(*)

     FROM products

     WHERE vendor_id = ?

     AND status = 'active'"
);

$stmt->execute([
    $vendorId
]);

$activeProducts =
    (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| Total Orders
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT COUNT(DISTINCT order_id)

     FROM order_items

     WHERE vendor_id = ?"
);

$stmt->execute([
    $vendorId
]);

$totalOrders =
    (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| Total Sales
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT
        COALESCE(
            SUM(
                oi.quantity * oi.price
            ),
            0
        )

     FROM order_items oi

     INNER JOIN orders o
        ON o.id = oi.order_id

     WHERE oi.vendor_id = ?

     AND o.payment_status = 'paid'"
);

$stmt->execute([
    $vendorId
]);

$totalSales =
    (float) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| Recent Orders
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT
        o.id AS order_id,
        o.order_status,
        o.payment_status,
        o.created_at,

        oi.quantity,
        oi.price,

        p.name AS product_name

     FROM order_items oi

     INNER JOIN orders o
        ON o.id = oi.order_id

     INNER JOIN products p
        ON p.id = oi.product_id

     WHERE oi.vendor_id = ?

     ORDER BY o.created_at DESC

     LIMIT 5"
);

$stmt->execute([
    $vendorId
]);

$recentOrders =
    $stmt->fetchAll();


$pageTitle =
    "Vendor Dashboard | "
    . APP_NAME;


require_once "../includes/header.php";

?>


<div
    class="d-flex justify-content-between align-items-center mb-4"
>

    <div>

        <h1 class="fw-bold">

            Vendor Dashboard

        </h1>

        <p class="text-muted mb-0">

            Welcome,
            <?= e(
                $vendor["store_name"]
            ) ?>

        </p>

    </div>

</div>


<!-- Statistics -->


<div
    class="row g-4 mb-4"
>


    <!-- Products -->


    <div class="col-md-6 col-xl-3">


        <div
            class="card border-0 shadow-sm h-100"
        >


            <div
                class="card-body"
            >


                <p class="text-muted mb-1">

                    Total Products

                </p>


                <h2 class="fw-bold">

                    <?= $productCount ?>

                </h2>


                <small class="text-success">

                    <?= $activeProducts ?>
                    active

                </small>


            </div>


        </div>


    </div>


    <!-- Orders -->

      <a
    href="<?= BASE_URL ?>vendor/orders.php"
    class="nav-link"
>
    <i class="bi bi-box-seam"></i>
    Orders
</a>
    <div class="col-md-6 col-xl-3">


        <div
            class="card border-0 shadow-sm h-100"
        >


            <div
                class="card-body"
            >


                <p class="text-muted mb-1">

                    Total Orders

                </p>


                <h2 class="fw-bold">

                    <?= $totalOrders ?>

                </h2>


            </div>


        </div>


    </div>


    <!-- Sales -->


    <div class="col-md-6 col-xl-3">


        <div
            class="card border-0 shadow-sm h-100"
        >


            <div
                class="card-body"
            >


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


    <!-- Vendor Status -->


    <div class="col-md-6 col-xl-3">


        <div
            class="card border-0 shadow-sm h-100"
        >


            <div
                class="card-body"
            >


                <p class="text-muted mb-1">

                    Store Status

                </p>


                <h4>

                    <span
                        class="badge text-bg-success"
                    >

                        Approved

                    </span>

                </h4>


            </div>


        </div>


    </div>


</div>


<!-- Quick Actions -->


<div
    class="card border-0 shadow-sm mb-4"
>


    <div
        class="card-body"
    >


        <h4 class="mb-3">

            Quick Actions

        </h4>


        <div
            class="d-flex gap-2 flex-wrap"
        >


            <a
                href="<?= BASE_URL ?>vendor/products.php"
                class="btn btn-dark"
            >

                <i class="bi bi-box-seam"></i>

                Manage Products

            </a>


            <a
                href="<?= BASE_URL ?>vendor/product-add.php"
                class="btn btn-outline-dark"
            >

                <i class="bi bi-plus-lg"></i>

                Add Product

            </a>


            <a
                href="<?= BASE_URL ?>vendor/orders.php"
                class="btn btn-outline-primary"
            >

                <i class="bi bi-cart"></i>

                View Orders

            </a>


        </div>


    </div>


</div>


<!-- Recent Orders -->


<div
    class="card border-0 shadow-sm"
>


    <div
        class="card-body"
    >


        <h4 class="mb-4">

            Recent Orders

        </h4>


        <?php if (
            empty(
                $recentOrders
            )
        ): ?>


            <p class="text-muted">

                You have no orders yet.

            </p>


        <?php else: ?>


            <div
                class="table-responsive"
            >


                <table
                    class="table table-hover"
                >


                    <thead>

                        <tr>

                            <th>
                                Order
                            </th>

                            <th>
                                Product
                            </th>

                            <th>
                                Quantity
                            </th>

                            <th>
                                Amount
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


                        <?php foreach (
                            $recentOrders
                            as $order
                        ): ?>


                            <tr>


                                <td>

                                    #<?= e(
                                        $order["order_id"]
                                    ) ?>

                                </td>


                                <td>

                                    <?= e(
                                        $order["product_name"]
                                    ) ?>

                                </td>


                                <td>

                                    <?= e(
                                        $order["quantity"]
                                    ) ?>

                                </td>


                                <td>

                                    ₹<?= number_format(
                                        $order["quantity"]
                                        * $order["price"],
                                        2
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
                                            $order["created_at"]
                                        )
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


<?php

require_once "../includes/footer.php";

?>