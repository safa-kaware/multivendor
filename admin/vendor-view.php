<?php

require_once "../config/app.php";

requireAdmin();


$vendorId =
    (int) (
        $_GET["id"]
        ?? 0
    );


if (
    $vendorId <= 0
) {

    redirect(
        BASE_URL
        . "admin/vendors.php"
    );

}


/*
|--------------------------------------------------------------------------
| Fetch Vendor
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT
        v.*,

        u.name AS owner_name,
        u.email AS owner_email,
        u.status AS user_status

     FROM vendors v

     INNER JOIN users u
        ON u.id = v.user_id

     WHERE v.id = ?

     LIMIT 1"
);

$stmt->execute([
    $vendorId
]);

$vendor =
    $stmt->fetch();


if (
    !$vendor
) {

    redirect(
        BASE_URL
        . "admin/vendors.php"
    );

}


/*
|--------------------------------------------------------------------------
| Fetch Vendor Products
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT
        id,
        name,
        price,
        stock,
        status,
        featured,
        image

     FROM products

     WHERE vendor_id = ?

     ORDER BY created_at DESC"
);

$stmt->execute([
    $vendorId
]);

$products =
    $stmt->fetchAll();


$pageTitle =
    "Vendor Details | "
    . APP_NAME;


require_once "includes/header.php";

?>


<div
    class="d-flex justify-content-between align-items-center mb-4"
>

    <h1 class="fw-bold">

        Vendor Details

    </h1>


    <a
        href="<?= BASE_URL ?>admin/vendors.php"
        class="btn btn-outline-secondary"
    >

        <i class="bi bi-arrow-left"></i>

        Back to Vendors

    </a>

</div>


<div
    class="card border-0 shadow-sm mb-4"
>

    <div
        class="card-body p-4"
    >

        <div class="row">


            <div class="col-md-3 text-center">


                <?php if (
                    !empty(
                        $vendor["logo"]
                    )
                ): ?>


                    <img
                        src="<?= BASE_URL ?>uploads/vendors/<?= e(
                            $vendor["logo"]
                        ) ?>"
                        alt="<?= e(
                            $vendor["store_name"]
                        ) ?>"
                        style="
                            width: 150px;
                            height: 150px;
                            object-fit: cover;
                        "
                        class="rounded-circle border"
                    >


                <?php else: ?>


                    <div
                        class="bg-light rounded-circle mx-auto d-flex align-items-center justify-content-center"
                        style="
                            width: 150px;
                            height: 150px;
                        "
                    >

                        <i
                            class="bi bi-shop fs-1 text-muted"
                        ></i>

                    </div>


                <?php endif; ?>


            </div>


            <div class="col-md-9">


                <h2>

                    <?= e(
                        $vendor["store_name"]
                    ) ?>

                </h2>


                <p class="text-muted">

                    <?= e(
                        $vendor["description"]
                        ?? "No description provided."
                    ) ?>

                </p>


                <hr>


                <p>

                    <strong>Owner:</strong>

                    <?= e(
                        $vendor["owner_name"]
                    ) ?>

                </p>


                <p>

                    <strong>Email:</strong>

                    <?= e(
                        $vendor["owner_email"]
                    ) ?>

                </p>


                <p>

                    <strong>Status:</strong>


                    <?php if (
                        $vendor["status"]
                        === "pending"
                    ): ?>

                        <span
                            class="badge text-bg-warning"
                        >

                            Pending

                        </span>


                    <?php elseif (
                        $vendor["status"]
                        === "approved"
                    ): ?>

                        <span
                            class="badge text-bg-success"
                        >

                            Approved

                        </span>


                    <?php elseif (
                        $vendor["status"]
                        === "rejected"
                    ): ?>

                        <span
                            class="badge text-bg-danger"
                        >

                            Rejected

                        </span>


                    <?php else: ?>

                        <span
                            class="badge text-bg-secondary"
                        >

                            Suspended

                        </span>

                    <?php endif; ?>


                </p>


                <p>

                    <strong>Registered:</strong>

                    <?= date(
                        "d M Y",
                        strtotime(
                            $vendor["created_at"]
                        )
                    ) ?>

                </p>


            </div>


        </div>

    </div>

</div>


<div
    class="card border-0 shadow-sm"
>


    <div
        class="card-body"
    >


        <h4 class="mb-4">

            Vendor Products

        </h4>


        <?php if (
            empty($products)
        ): ?>


            <p class="text-muted">

                This vendor has not added any products yet.

            </p>


        <?php else: ?>


            <div
                class="table-responsive"
            >


                <table
                    class="table table-hover align-middle"
                >


                    <thead>

                        <tr>

                            <th>Image</th>

                            <th>Product</th>

                            <th>Price</th>

                            <th>Stock</th>

                            <th>Status</th>

                        </tr>

                    </thead>


                    <tbody>


                        <?php foreach (
                            $products
                            as $product
                        ): ?>


                            <tr>


                                <td>


                                    <?php if (
                                        !empty(
                                            $product["image"]
                                        )
                                    ): ?>


                                        <img
                                            src="<?= BASE_URL ?>uploads/products/<?= e(
                                                $product["image"]
                                            ) ?>"
                                            style="
                                                width: 50px;
                                                height: 50px;
                                                object-fit: cover;
                                            "
                                            class="rounded"
                                        >


                                    <?php endif; ?>


                                </td>


                                <td>

                                    <?= e(
                                        $product["name"]
                                    ) ?>

                                </td>


                                <td>

                                    ₹<?= number_format(
                                        $product["price"],
                                        2
                                    ) ?>

                                </td>


                                <td>

                                    <?= e(
                                        $product["stock"]
                                    ) ?>

                                </td>


                                <td>


                                    <?php if (
                                        $product["status"]
                                        === "active"
                                    ): ?>


                                        <span
                                            class="badge text-bg-success"
                                        >

                                            Active

                                        </span>


                                    <?php else: ?>


                                        <span
                                            class="badge text-bg-secondary"
                                        >

                                            Inactive

                                        </span>


                                    <?php endif; ?>


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

require_once "includes/footer.php";

?>