<?php

require_once "../config/app.php";

// Require admin login
requireAdmin();

$pageTitle = "Manage Products | " . APP_NAME;

/*
|--------------------------------------------------------------------------
| FETCH ALL PRODUCTS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT
        p.id,
        p.name,
        p.slug,
        p.price,
        p.stock,
        p.sku,
        p.image,
        p.status,
        p.featured,
        p.created_at,

        v.id AS vendor_id,
        v.store_name,

        c.id AS category_id,
        c.name AS category_name

     FROM products p

     INNER JOIN vendors v
        ON p.vendor_id = v.id

     INNER JOIN categories c
        ON p.category_id = c.id

     ORDER BY p.created_at DESC"
);

$stmt->execute();

$products = $stmt->fetchAll();

?>

<?php require_once "../includes/header.php"; ?>


<div class="container-fluid py-5">

    <!-- PAGE HEADER -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h1 class="fw-bold">
                Manage Products
            </h1>

            <p class="text-muted mb-0">
                View and manage all products listed by vendors.
            </p>

        </div>

        <a
            href="<?= BASE_URL ?>admin/index.php"
            class="btn btn-secondary"
        >
            Back to Dashboard
        </a>

    </div>


    <!-- PRODUCT COUNT -->

    <div class="alert alert-info">

        Total Products:

        <strong>
            <?= count($products) ?>
        </strong>

    </div>


    <!-- PRODUCTS TABLE -->

    <div class="card shadow-sm border-0">

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead class="table-dark">

                        <tr>

                            <th>
                                ID
                            </th>

                            <th>
                                Product
                            </th>

                            <th>
                                Vendor
                            </th>

                            <th>
                                Category
                            </th>

                            <th>
                                Price
                            </th>

                            <th>
                                Stock
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Featured
                            </th>

                            <th>
                                Created
                            </th>

                            <th>
                                Actions
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (empty($products)): ?>

                        <tr>

                            <td
                                colspan="10"
                                class="text-center py-5"
                            >

                                <h5>
                                    No products found.
                                </h5>

                                <p class="text-muted mb-0">
                                    Vendors have not added any products yet.
                                </p>

                            </td>

                        </tr>

                    <?php else: ?>


                        <?php foreach ($products as $product): ?>

                            <tr>


                                <!-- ID -->

                                <td>

                                    <?= (int) $product["id"] ?>

                                </td>


                                <!-- PRODUCT -->

                                <td>

                                    <div class="d-flex align-items-center">

                                        <?php if (
                                            !empty(
                                                $product["image"]
                                            )
                                        ): ?>

                                            <img
                                                src="<?= BASE_URL ?>uploads/products/<?= e(
                                                    $product["image"]
                                                ) ?>"
                                                alt="<?= e(
                                                    $product["name"]
                                                ) ?>"
                                                width="60"
                                                height="60"
                                                class="rounded me-3"
                                                style="object-fit: cover;"
                                            >

                                        <?php else: ?>

                                            <div
                                                class="bg-light rounded me-3 d-flex align-items-center justify-content-center"
                                                style="width:60px;height:60px;"
                                            >

                                                <span class="text-muted">
                                                    No Image
                                                </span>

                                            </div>

                                        <?php endif; ?>


                                        <div>

                                            <strong>

                                                <?= e(
                                                    $product["name"]
                                                ) ?>

                                            </strong>

                                            <?php if (
                                                !empty(
                                                    $product["sku"]
                                                )
                                            ): ?>

                                                <small
                                                    class="d-block text-muted"
                                                >

                                                    SKU:

                                                    <?= e(
                                                        $product["sku"]
                                                    ) ?>

                                                </small>

                                            <?php endif; ?>

                                        </div>

                                    </div>

                                </td>


                                <!-- VENDOR -->

                                <td>

                                    <?= e(
                                        $product["store_name"]
                                    ) ?>

                                </td>


                                <!-- CATEGORY -->

                                <td>

                                    <?= e(
                                        $product["category_name"]
                                    ) ?>

                                </td>


                                <!-- PRICE -->

                                <td>

                                    ₹<?= number_format(
                                        (float)
                                        $product["price"],
                                        2
                                    ) ?>

                                </td>


                                <!-- STOCK -->

                                <td>

                                    <?php if (
                                        (int)
                                        $product["stock"]
                                        > 0
                                    ): ?>

                                        <span
                                            class="badge bg-success"
                                        >

                                            <?= (int)
                                                $product["stock"] ?>

                                        </span>

                                    <?php else: ?>

                                        <span
                                            class="badge bg-danger"
                                        >

                                            Out of Stock

                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- STATUS -->

                                <td>

                                    <?php if (
                                        $product["status"]
                                        === "active"
                                    ): ?>

                                        <span
                                            class="badge bg-success"
                                        >

                                            Active

                                        </span>

                                    <?php else: ?>

                                        <span
                                            class="badge bg-secondary"
                                        >

                                            Inactive

                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- FEATURED -->

                                <td>

                                    <?php if (
                                        (int)
                                        $product["featured"]
                                        === 1
                                    ): ?>

                                        <span
                                            class="badge bg-warning text-dark"
                                        >

                                            Featured

                                        </span>

                                    <?php else: ?>

                                        <span
                                            class="badge bg-light text-dark border"
                                        >

                                            No

                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- CREATED -->

                                <td>

                                    <?= date(
                                        "d M Y",
                                        strtotime(
                                            $product[
                                                "created_at"
                                            ]
                                        )
                                    ) ?>

                                </td>


                                <!-- ACTIONS -->

                                <td>

                                    <div class="d-flex gap-2">

                                        <!-- VIEW -->

                                        <a
                                            href="<?= BASE_URL ?>product.php?id=<?= (int) $product["id"] ?>"
                                            class="btn btn-sm btn-primary"
                                        >

                                            View

                                        </a>


                                        <!-- STATUS -->

                                        <?php if (
                                            $product["status"]
                                            === "active"
                                        ): ?>

                                            <a
                                                href="<?= BASE_URL ?>admin/product-status.php?id=<?= (int) $product["id"] ?>&status=inactive"
                                                class="btn btn-sm btn-warning"
                                                onclick="return confirm('Are you sure you want to deactivate this product?');"
                                            >

                                                Deactivate

                                            </a>

                                        <?php else: ?>

                                            <a
                                                href="<?= BASE_URL ?>admin/product-status.php?id=<?= (int) $product["id"] ?>&status=active"
                                                class="btn btn-sm btn-success"
                                                onclick="return confirm('Are you sure you want to activate this product?');"
                                            >

                                                Activate

                                            </a>

                                        <?php endif; ?>


                                        <!-- FEATURED -->

                                        <?php if (
                                            (int)
                                            $product["featured"]
                                            === 1
                                        ): ?>

                                            <a
                                                href="<?= BASE_URL ?>admin/product-featured.php?id=<?= (int) $product["id"] ?>&featured=0"
                                                class="btn btn-sm btn-outline-secondary"
                                            >

                                                Unfeature

                                            </a>

                                        <?php else: ?>

                                            <a
                                                href="<?= BASE_URL ?>admin/product-featured.php?id=<?= (int) $product["id"] ?>&featured=1"
                                                class="btn btn-sm btn-outline-primary"
                                            >

                                                Feature

                                            </a>

                                        <?php endif; ?>


                                        <!-- DELETE -->

                                        <a
                                            href="<?= BASE_URL ?>admin/product-delete.php?id=<?= (int) $product["id"] ?>"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('Are you sure you want to permanently delete this product? This action cannot be undone.');"
                                        >

                                            Delete

                                        </a>

                                    </div>

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


<?php require_once "../includes/footer.php"; ?>