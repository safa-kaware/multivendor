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
| SEARCH
|--------------------------------------------------------------------------
*/

$search =
    trim(
        $_GET["search"]
        ?? ""
    );


/*
|--------------------------------------------------------------------------
| STATUS FILTER
|--------------------------------------------------------------------------
*/

$statusFilter =
    $_GET["status"]
    ?? "";


/*
|--------------------------------------------------------------------------
| BUILD QUERY
|--------------------------------------------------------------------------
*/

$sql =

    "SELECT

        p.id,
        p.name,
        p.description,
        p.price,
        p.stock,
        p.image,
        p.status,
        p.created_at

     FROM products p

     WHERE p.vendor_id = ?";


$params = [

    $vendorId

];


/*
|--------------------------------------------------------------------------
| SEARCH FILTER
|--------------------------------------------------------------------------
*/

if (
    $search !== ""
) {

    $sql .=

        " AND (

            p.name LIKE ?

            OR

            p.description LIKE ?

        )";


    $searchTerm =
        "%"
        . $search
        . "%";


    $params[] =
        $searchTerm;


    $params[] =
        $searchTerm;

}


/*
|--------------------------------------------------------------------------
| STATUS FILTER
|--------------------------------------------------------------------------
*/

$allowedStatuses = [

    "active",

    "inactive",

    "draft"

];


if (
    in_array(
        $statusFilter,
        $allowedStatuses,
        true
    )
) {

    $sql .=

        " AND p.status = ?";


    $params[] =
        $statusFilter;

}


/*
|--------------------------------------------------------------------------
| ORDER
|--------------------------------------------------------------------------
*/

$sql .=

    " ORDER BY
        p.created_at DESC";


/*
|--------------------------------------------------------------------------
| EXECUTE
|--------------------------------------------------------------------------
*/

$stmt =
    $pdo->prepare(
        $sql
    );


$stmt->execute(
    $params
);


$products =
    $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| PRODUCT STATISTICS
|--------------------------------------------------------------------------
*/

$statsStmt =
    $pdo->prepare(

        "SELECT

            COUNT(*) AS total_products,

            SUM(
                CASE
                    WHEN status = 'active'
                    THEN 1
                    ELSE 0
                END
            ) AS active_products,

            SUM(
                CASE
                    WHEN status = 'inactive'
                    THEN 1
                    ELSE 0
                END
            ) AS inactive_products,

            SUM(
                CASE
                    WHEN stock <= 0
                    THEN 1
                    ELSE 0
                END
            ) AS out_of_stock

         FROM products

         WHERE vendor_id = ?"

    );


$statsStmt->execute([

    $vendorId

]);


$stats =
    $statsStmt->fetch();


/*
|--------------------------------------------------------------------------
| PAGE TITLE
|--------------------------------------------------------------------------
*/

$pageTitle =

    "My Products | "

    . APP_NAME;


/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

require_once "../includes/header.php";

?>


<div class="container-fluid py-4">
    <?php if (
    !empty(
        $_SESSION["success_message"]
    )
): ?>

    <div
        class="alert alert-success alert-dismissible fade show shadow-sm"
        role="alert"
    >

        <i class="bi bi-check-circle me-2"></i>

        <?= e(
            $_SESSION["success_message"]
        ) ?>


        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert"
        ></button>

    </div>


    <?php

    unset(
        $_SESSION["success_message"]
    );

    ?>

<?php endif; ?>


    <!-- HEADER -->

    <div
        class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4"
    >


        <div>

            <h1 class="fw-bold mb-1">

                My Products

            </h1>


            <p class="text-muted mb-0">

                Manage your marketplace products and inventory.

            </p>

        </div>


        <a
            href="<?= BASE_URL ?>vendor/product-add.php"
            class="btn btn-dark"
        >

            <i class="bi bi-plus-lg me-1"></i>

            Add Product

        </a>


    </div>



    <!-- STATISTICS -->

    <div class="row g-3 mb-4">


        <!-- TOTAL -->

        <div class="col-6 col-xl-3">

            <div
                class="card border-0 shadow-sm h-100"
            >

                <div class="card-body">

                    <div
                        class="d-flex justify-content-between align-items-center"
                    >

                        <div>

                            <small
                                class="text-muted"
                            >

                                Total Products

                            </small>


                            <h3 class="fw-bold mb-0">

                                <?= (int)
                                    (
                                        $stats[
                                            "total_products"
                                        ]
                                        ?? 0
                                    ) ?>

                            </h3>

                        </div>


                        <div
                            class="bg-light rounded-circle p-3"
                        >

                            <i
                                class="bi bi-box-seam fs-4"
                            ></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>



        <!-- ACTIVE -->

        <div class="col-6 col-xl-3">

            <div
                class="card border-0 shadow-sm h-100"
            >

                <div class="card-body">

                    <div
                        class="d-flex justify-content-between align-items-center"
                    >

                        <div>

                            <small
                                class="text-muted"
                            >

                                Active

                            </small>


                            <h3 class="fw-bold text-success mb-0">

                                <?= (int)
                                    (
                                        $stats[
                                            "active_products"
                                        ]
                                        ?? 0
                                    ) ?>

                            </h3>

                        </div>


                        <div
                            class="bg-success-subtle rounded-circle p-3"
                        >

                            <i
                                class="bi bi-check-circle fs-4 text-success"
                            ></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>



        <!-- INACTIVE -->

        <div class="col-6 col-xl-3">

            <div
                class="card border-0 shadow-sm h-100"
            >

                <div class="card-body">

                    <div
                        class="d-flex justify-content-between align-items-center"
                    >

                        <div>

                            <small
                                class="text-muted"
                            >

                                Inactive

                            </small>


                            <h3 class="fw-bold text-secondary mb-0">

                                <?= (int)
                                    (
                                        $stats[
                                            "inactive_products"
                                        ]
                                        ?? 0
                                    ) ?>

                            </h3>

                        </div>


                        <div
                            class="bg-secondary-subtle rounded-circle p-3"
                        >

                            <i
                                class="bi bi-eye-slash fs-4 text-secondary"
                            ></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>



        <!-- OUT OF STOCK -->

        <div class="col-6 col-xl-3">

            <div
                class="card border-0 shadow-sm h-100"
            >

                <div class="card-body">

                    <div
                        class="d-flex justify-content-between align-items-center"
                    >

                        <div>

                            <small
                                class="text-muted"
                            >

                                Out of Stock

                            </small>


                            <h3 class="fw-bold text-danger mb-0">

                                <?= (int)
                                    (
                                        $stats[
                                            "out_of_stock"
                                        ]
                                        ?? 0
                                    ) ?>

                            </h3>

                        </div>


                        <div
                            class="bg-danger-subtle rounded-circle p-3"
                        >

                            <i
                                class="bi bi-exclamation-circle fs-4 text-danger"
                            ></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>


    </div>



    <!-- FILTERS -->

    <div
        class="card border-0 shadow-sm mb-4"
    >

        <div class="card-body">


            <form
                method="GET"
                class="row g-2"
            >


                <div class="col-md-6">

                    <div class="input-group">

                        <span
                            class="input-group-text bg-white"
                        >

                            <i class="bi bi-search"></i>

                        </span>


                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            placeholder="Search products..."
                            value="<?= e(
                                $search
                            ) ?>"
                        >

                    </div>

                </div>


                <div class="col-md-3">

                    <select
                        name="status"
                        class="form-select"
                    >

                        <option value="">

                            All Statuses

                        </option>


                        <option
                            value="active"
                            <?= $statusFilter === "active"
                                ? "selected"
                                : "" ?>
                        >

                            Active

                        </option>


                        <option
                            value="inactive"
                            <?= $statusFilter === "inactive"
                                ? "selected"
                                : "" ?>
                        >

                            Inactive

                        </option>


                        <option
                            value="draft"
                            <?= $statusFilter === "draft"
                                ? "selected"
                                : "" ?>
                        >

                            Draft

                        </option>

                    </select>

                </div>


                <div class="col-md-3 d-flex gap-2">

                    <button
                        type="submit"
                        class="btn btn-dark flex-grow-1"
                    >

                        Filter

                    </button>


                    <a
                        href="<?= BASE_URL ?>vendor/products.php"
                        class="btn btn-outline-secondary"
                    >

                        <i class="bi bi-arrow-counterclockwise"></i>

                    </a>

                </div>


            </form>


        </div>

    </div>



    <!-- PRODUCTS -->

    <div
        class="card border-0 shadow-sm"
    >

        <div class="card-body p-0">


            <?php if (
                empty($products)
            ): ?>


                <div
                    class="text-center py-5 px-3"
                >

                    <div
                        class="display-4 text-muted mb-3"
                    >

                        <i class="bi bi-box-seam"></i>

                    </div>


                    <h4 class="fw-bold">

                        No products found

                    </h4>


                    <p class="text-muted">

                        Start selling by adding your first product.

                    </p>


                    <a
                        href="<?= BASE_URL ?>vendor/product-add.php"
                        class="btn btn-dark"
                    >

                        <i class="bi bi-plus-lg me-1"></i>

                        Add Your First Product

                    </a>

                </div>


            <?php else: ?>


                <div class="table-responsive">


                    <table
                        class="table table-hover align-middle mb-0"
                    >


                        <thead
                            class="table-light"
                        >

                            <tr>

                                <th
                                    class="ps-4"
                                >

                                    Product

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

                                    Added

                                </th>

                                <th
                                    class="text-end pe-4"
                                >

                                    Actions

                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php foreach (
                                $products
                                as $product
                            ): ?>


                                <tr>


                                    <!-- PRODUCT -->

                                    <td class="ps-4">


                                        <div
                                            class="d-flex align-items-center gap-3"
                                        >


                                            <?php if (
                                                !empty(
                                                    $product[
                                                        "image"
                                                    ]
                                                )
                                            ): ?>


                                                <img
                                                    src="<?= BASE_URL ?>uploads/products/<?= e(
                                                        $product[
                                                            "image"
                                                        ]
                                                    ) ?>"
                                                    alt="<?= e(
                                                        $product[
                                                            "name"
                                                        ]
                                                    ) ?>"
                                                    class="rounded"
                                                    style="
                                                        width: 60px;
                                                        height: 60px;
                                                        object-fit: cover;
                                                    "
                                                >


                                            <?php else: ?>


                                                <div
                                                    class="bg-light rounded d-flex align-items-center justify-content-center"
                                                    style="
                                                        width: 60px;
                                                        height: 60px;
                                                    "
                                                >

                                                    <i
                                                        class="bi bi-image text-muted fs-4"
                                                    ></i>

                                                </div>


                                            <?php endif; ?>


                                            <div>

                                                <strong>

                                                    <?= e(
                                                        $product[
                                                            "name"
                                                        ]
                                                    ) ?>

                                                </strong>


                                                <?php if (
                                                    !empty(
                                                        $product[
                                                            "description"
                                                        ]
                                                    )
                                                ): ?>


                                                    <div>

                                                        <small
                                                            class="text-muted"
                                                        >

                                                            <?= e(
                                                                mb_strimwidth(
                                                                    $product[
                                                                        "description"
                                                                    ],
                                                                    0,
                                                                    55,
                                                                    "..."
                                                                )
                                                            ) ?>

                                                        </small>

                                                    </div>


                                                <?php endif; ?>


                                            </div>


                                        </div>


                                    </td>



                                    <!-- PRICE -->

                                    <td>

                                        <strong>

                                            ₹<?= number_format(

                                                (float)
                                                $product[
                                                    "price"
                                                ],

                                                2

                                            ) ?>

                                        </strong>

                                    </td>



                                    <!-- STOCK -->

                                    <td>


                                        <?php

                                        $stock =
                                            (int)
                                            $product[
                                                "stock"
                                            ];

                                        ?>


                                        <?php if (
                                            $stock <= 0
                                        ): ?>


                                            <span
                                                class="badge text-bg-danger"
                                            >

                                                Out of Stock

                                            </span>


                                        <?php elseif (
                                            $stock <= 5
                                        ): ?>


                                            <span
                                                class="badge text-bg-warning"
                                            >

                                                <?= $stock ?>

                                                left

                                            </span>


                                        <?php else: ?>


                                            <span
                                                class="badge text-bg-success"
                                            >

                                                <?= $stock ?>

                                            </span>


                                        <?php endif; ?>


                                    </td>



                                    <!-- STATUS -->

                                    <td>


                                        <?php if (
                                            $product[
                                                "status"
                                            ]
                                            === "active"
                                        ): ?>


                                            <span
                                                class="badge rounded-pill text-bg-success"
                                            >

                                                Active

                                            </span>


                                        <?php elseif (
                                            $product[
                                                "status"
                                            ]
                                            === "inactive"
                                        ): ?>


                                            <span
                                                class="badge rounded-pill text-bg-secondary"
                                            >

                                                Inactive

                                            </span>


                                        <?php else: ?>


                                            <span
                                                class="badge rounded-pill text-bg-warning"
                                            >

                                                <?= e(
                                                    ucfirst(
                                                        $product[
                                                            "status"
                                                        ]
                                                    )
                                                ) ?>

                                            </span>


                                        <?php endif; ?>


                                    </td>



                                    <!-- DATE -->

                                    <td>

                                        <small>

                                            <?= date(
                                                "d M Y",
                                                strtotime(
                                                    $product[
                                                        "created_at"
                                                    ]
                                                )
                                            ) ?>

                                        </small>

                                    </td>



                                    <!-- ACTIONS -->

                                    <td
                                        class="text-end pe-4"
                                    >


                                        <div
                                            class="dropdown"
                                        >


                                            <button
                                                class="btn btn-sm btn-light border"
                                                type="button"
                                                data-bs-toggle="dropdown"
                                            >

                                                <i
                                                    class="bi bi-three-dots-vertical"
                                                ></i>

                                            </button>


                                            <ul
                                                class="dropdown-menu dropdown-menu-end"
                                            >


                                                <li>

                                                    <a
                                                        class="dropdown-item"
                                                        href="<?= BASE_URL ?>product.php?id=<?= (int)
                                                            $product[
                                                                "id"
                                                            ] ?>"
                                                    >

                                                        <i
                                                            class="bi bi-eye me-2"
                                                        ></i>

                                                        View Product

                                                    </a>

                                                </li>


                                                <li>

                                                    <a
                                                        class="dropdown-item"
                                                        href="<?= BASE_URL ?>vendor/product-edit.php?id=<?= (int)
                                                            $product[
                                                                "id"
                                                            ] ?>"
                                                    >

                                                        <i
                                                            class="bi bi-pencil me-2"
                                                        ></i>

                                                        Edit Product

                                                    </a>

                                                </li>


                                                <li>
                                                    <hr
                                                        class="dropdown-divider"
                                                    >
                                                </li>


                                                <li>

                                                    <a
                                                        class="dropdown-item text-danger"
                                                        href="<?= BASE_URL ?>vendor/product-delete.php?id=<?= (int)
                                                            $product[
                                                                "id"
                                                            ] ?>"
                                                        onclick="return confirm('Are you sure you want to deactivate this product?');"
                                                    >

                                                        <i
                                                            class="bi bi-trash me-2"
                                                        ></i>

                                                        Deactivate

                                                    </a>

                                                </li>


                                            </ul>


                                        </div>


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


<?php

require_once "../includes/footer.php";

?>