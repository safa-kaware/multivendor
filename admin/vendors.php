<?php

require_once "../config/app.php";

requireAdmin();


/*
|--------------------------------------------------------------------------
| Fetch Vendors
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query(
    "SELECT
        v.id,
        v.user_id,
        v.store_name,
        v.description,
        v.logo,
        v.status,
        v.created_at,

        u.name AS owner_name,
        u.email AS owner_email,

        COUNT(p.id) AS product_count

     FROM vendors v

     INNER JOIN users u
        ON u.id = v.user_id

     LEFT JOIN products p
        ON p.vendor_id = v.id

     GROUP BY
        v.id,
        v.user_id,
        v.store_name,
        v.description,
        v.logo,
        v.status,
        v.created_at,
        u.name,
        u.email

     ORDER BY v.created_at DESC"
);

$vendors = $stmt->fetchAll();


$pageTitle =
    "Manage Vendors | "
    . APP_NAME;


require_once "includes/header.php";

?>


<div
    class="d-flex justify-content-between align-items-center mb-4"
>

    <div>

        <h1 class="fw-bold">

            Vendors

        </h1>

        <p class="text-muted mb-0">

            Manage marketplace sellers and vendor applications.

        </p>

    </div>

</div>


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

                        <th>Logo</th>

                        <th>Store</th>

                        <th>Owner</th>

                        <th>Email</th>

                        <th>Products</th>

                        <th>Status</th>

                        <th>Created</th>

                        <th>Actions</th>

                    </tr>

                </thead>


                <tbody>


                    <?php if (
                        empty($vendors)
                    ): ?>


                        <tr>

                            <td
                                colspan="8"
                                class="text-center py-5 text-muted"
                            >

                                No vendors found.

                            </td>

                        </tr>


                    <?php else: ?>


                        <?php foreach (
                            $vendors
                            as $vendor
                        ): ?>


                            <tr>


                                <!-- Logo -->


                                <td>

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
                                                width: 55px;
                                                height: 55px;
                                                object-fit: cover;
                                            "
                                            class="rounded-circle border"
                                        >


                                    <?php else: ?>


                                        <div
                                            class="bg-light rounded-circle d-flex align-items-center justify-content-center"
                                            style="
                                                width: 55px;
                                                height: 55px;
                                            "
                                        >

                                            <i
                                                class="bi bi-shop text-muted"
                                            ></i>

                                        </div>


                                    <?php endif; ?>


                                </td>


                                <!-- Store -->


                                <td>

                                    <strong>

                                        <?= e(
                                            $vendor["store_name"]
                                        ) ?>

                                    </strong>


                                    <?php if (
                                        !empty(
                                            $vendor["description"]
                                        )
                                    ): ?>


                                        <br>

                                        <small
                                            class="text-muted"
                                        >

                                            <?= e(
                                                mb_strimwidth(
                                                    $vendor["description"],
                                                    0,
                                                    50,
                                                    "..."
                                                )
                                            ) ?>

                                        </small>


                                    <?php endif; ?>


                                </td>


                                <!-- Owner -->


                                <td>

                                    <?= e(
                                        $vendor["owner_name"]
                                    ) ?>

                                </td>


                                <!-- Email -->


                                <td>

                                    <?= e(
                                        $vendor["owner_email"]
                                    ) ?>

                                </td>


                                <!-- Products -->


                                <td>

                                    <span
                                        class="badge text-bg-secondary"
                                    >

                                        <?= e(
                                            $vendor["product_count"]
                                        ) ?>

                                    </span>

                                </td>


                                <!-- Status -->


                                <td>


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


                                </td>


                                <!-- Created -->


                                <td>

                                    <?= date(
                                        "d M Y",
                                        strtotime(
                                            $vendor["created_at"]
                                        )
                                    ) ?>

                                </td>


                                <!-- Actions -->


                                <td>


                                    <div
                                        class="d-flex gap-2 flex-wrap"
                                    >


                                        <a
                                            href="<?= BASE_URL ?>admin/vendor-view.php?id=<?= e(
                                                $vendor["id"]
                                            ) ?>"
                                            class="btn btn-sm btn-outline-dark"
                                        >

                                            <i
                                                class="bi bi-eye"
                                            ></i>

                                            View

                                        </a>


                                        <?php if (
                                            $vendor["status"]
                                            !== "approved"
                                        ): ?>


                                            <a
                                                href="<?= BASE_URL ?>admin/vendor-approve.php?id=<?= e(
                                                    $vendor["id"]
                                                ) ?>"
                                                class="btn btn-sm btn-outline-success"
                                                onclick="return confirm('Approve this vendor?');"
                                            >

                                                <i
                                                    class="bi bi-check-lg"
                                                ></i>

                                                Approve

                                            </a>


                                        <?php endif; ?>


                                        <?php if (
                                            $vendor["status"]
                                            !== "rejected"
                                        ): ?>


                                            <a
                                                href="<?= BASE_URL ?>admin/vendor-reject.php?id=<?= e(
                                                    $vendor["id"]
                                                ) ?>"
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Reject this vendor?');"
                                            >

                                                <i
                                                    class="bi bi-x-lg"
                                                ></i>

                                                Reject

                                            </a>


                                        <?php endif; ?>


                                        <?php if (
                                            $vendor["status"]
                                            === "approved"
                                        ): ?>


                                            <a
                                                href="<?= BASE_URL ?>admin/vendor-suspend.php?id=<?= e(
                                                    $vendor["id"]
                                                ) ?>"
                                                class="btn btn-sm btn-outline-warning"
                                                onclick="return confirm('Suspend this vendor?');"
                                            >

                                                Suspend

                                            </a>


                                        <?php endif; ?>


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


<?php

require_once "includes/footer.php";

?>