<?php

require_once "../config/app.php";


requireAdmin();


/*
|--------------------------------------------------------------------------
| Date Range Filters
|--------------------------------------------------------------------------
*/

$startDate =
    trim($_GET["start_date"] ?? "")
        ?: date("Y-m-d", strtotime("-30 days"));

$endDate =
    trim($_GET["end_date"] ?? "")
        ?: date("Y-m-d");


/*
|--------------------------------------------------------------------------
| Fetch Orders In Range
|--------------------------------------------------------------------------
*/

$stmt =
    $pdo->prepare(
        "SELECT
            o.id,
            o.total_amount,
            o.discount_amount,
            o.shipping_amount,
            o.payment_method,
            o.payment_status,
            o.order_status,
            o.created_at,

            u.name AS customer_name,
            u.email AS customer_email

         FROM orders o

         INNER JOIN users u
            ON u.id = o.user_id

         WHERE DATE(o.created_at) BETWEEN ? AND ?

         ORDER BY o.created_at DESC"
    );

$stmt->execute([$startDate, $endDate]);

$orders =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| CSV EXPORT
|--------------------------------------------------------------------------
*/

if (
    isset($_GET["export"])
    && $_GET["export"] === "csv"
) {

    header("Content-Type: text/csv; charset=utf-8");

    header(
        "Content-Disposition: attachment; filename=sales-report-"
        . $startDate
        . "-to-"
        . $endDate
        . ".csv"
    );

    $output = fopen("php://output", "w");

    // Byte-order mark so Excel opens UTF-8 correctly
    fwrite($output, "\xEF\xBB\xBF");

    fputcsv($output, [
        "Order ID",
        "Customer Name",
        "Customer Email",
        "Total Amount",
        "Discount",
        "Shipping",
        "Payment Method",
        "Payment Status",
        "Order Status",
        "Date",
    ]);

    foreach ($orders as $order) {

        fputcsv($output, [
            $order["id"],
            $order["customer_name"],
            $order["customer_email"],
            number_format($order["total_amount"], 2),
            number_format($order["discount_amount"], 2),
            number_format($order["shipping_amount"], 2),
            ucfirst($order["payment_method"]),
            ucfirst($order["payment_status"]),
            ucfirst($order["order_status"]),
            date("Y-m-d H:i", strtotime($order["created_at"])),
        ]);

    }

    fclose($output);

    exit;

}


/*
|--------------------------------------------------------------------------
| Summary Stats
|--------------------------------------------------------------------------
*/

$totalOrders = count($orders);

$totalRevenue = 0;

$totalDiscount = 0;

foreach ($orders as $order) {

    $totalRevenue += (float) $order["total_amount"];

    $totalDiscount += (float) $order["discount_amount"];

}

$averageOrderValue =
    $totalOrders > 0
        ? $totalRevenue / $totalOrders
        : 0;


/*
|--------------------------------------------------------------------------
| Top Selling Products In Range
|--------------------------------------------------------------------------
*/

$topProductsStmt =
    $pdo->prepare(
        "SELECT
            p.id,
            p.name,

            SUM(oi.quantity) AS total_quantity,
            SUM(oi.quantity * oi.price) AS total_revenue

         FROM order_items oi

         INNER JOIN orders o
            ON o.id = oi.order_id

         INNER JOIN products p
            ON p.id = oi.product_id

         WHERE DATE(o.created_at) BETWEEN ? AND ?

         GROUP BY
            p.id,
            p.name

         ORDER BY
            total_quantity DESC

         LIMIT 10"
    );

$topProductsStmt->execute([$startDate, $endDate]);

$topProducts =
    $topProductsStmt->fetchAll(PDO::FETCH_ASSOC);


$pageTitle =
    "Reports | "
    . APP_NAME;


require_once "includes/header.php";

?>


<div class="mb-4">

    <h1 class="fw-bold">
        Sales Reports
    </h1>

    <p class="text-muted mb-0">
        Order and revenue reports for a selected date range.
    </p>

</div>


<div class="card border-0 shadow-sm mb-4">

    <div class="card-body">

        <form method="GET" class="row g-3 align-items-end">

            <div class="col-md-3">
                <label class="form-label">Start Date</label>
                <input
                    type="date"
                    name="start_date"
                    class="form-control"
                    value="<?= e($startDate) ?>"
                >
            </div>

            <div class="col-md-3">
                <label class="form-label">End Date</label>
                <input
                    type="date"
                    name="end_date"
                    class="form-control"
                    value="<?= e($endDate) ?>"
                >
            </div>

            <div class="col-md-3">
                <button type="submit" class="btn btn-dark">
                    Filter
                </button>
            </div>

            <div class="col-md-3 text-md-end">
                <a
                    href="<?= BASE_URL ?>admin/reports.php?start_date=<?= e($startDate) ?>&end_date=<?= e($endDate) ?>&export=csv"
                    class="btn btn-outline-dark"
                >
                    <i class="bi bi-download"></i>
                    Export CSV
                </a>
            </div>

        </form>

    </div>

</div>


<div class="row g-4 mb-4">

    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <p class="text-muted mb-1">Total Orders</p>
                <h3 class="fw-bold mb-0"><?= e($totalOrders) ?></h3>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <p class="text-muted mb-1">Total Revenue</p>
                <h3 class="fw-bold mb-0">₹<?= number_format($totalRevenue, 2) ?></h3>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <p class="text-muted mb-1">Average Order Value</p>
                <h3 class="fw-bold mb-0">₹<?= number_format($averageOrderValue, 2) ?></h3>
            </div>
        </div>
    </div>

</div>


<div class="card border-0 shadow-sm mb-4">

    <div class="card-body">

        <h5 class="fw-bold mb-3">Top Selling Products</h5>

        <div class="table-responsive">

            <table class="table table-hover align-middle">

                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Units Sold</th>
                        <th>Revenue</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (empty($topProducts)): ?>

                        <tr>
                            <td colspan="3" class="text-center py-4 text-muted">
                                No sales in this date range.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($topProducts as $product): ?>

                            <tr>
                                <td><?= e($product["name"]) ?></td>
                                <td><?= e($product["total_quantity"]) ?></td>
                                <td>₹<?= number_format($product["total_revenue"], 2) ?></td>
                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>


<div class="card border-0 shadow-sm">

    <div class="card-body">

        <h5 class="fw-bold mb-3">Orders</h5>

        <div class="table-responsive">

            <table class="table table-hover align-middle">

                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (empty($orders)): ?>

                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                No orders in this date range.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($orders as $order): ?>

                            <tr>
                                <td>#<?= e($order["id"]) ?></td>
                                <td>
                                    <?= e($order["customer_name"]) ?>
                                    <div class="text-muted small"><?= e($order["customer_email"]) ?></div>
                                </td>
                                <td>₹<?= number_format($order["total_amount"], 2) ?></td>
                                <td><?= e(ucfirst($order["payment_method"])) ?></td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?= e(ucfirst($order["order_status"])) ?>
                                    </span>
                                </td>
                                <td><?= e(date("d M Y", strtotime($order["created_at"]))) ?></td>
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