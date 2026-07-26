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
| Fetch Coupons
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query(
    "SELECT
        id,
        code,
        discount_type,
        value,
        minimum_amount,
        maximum_discount,
        usage_limit,
        used_count,
        expiry,
        status,
        created_at

     FROM coupons

     ORDER BY
        created_at DESC"
);

$coupons =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


$pageTitle =
    "Manage Coupons | "
    . APP_NAME;


require_once "includes/header.php";

?>


<div
    class="d-flex justify-content-between align-items-center mb-4"
>


    <div>

        <h1 class="fw-bold">

            Coupons

        </h1>


        <p class="text-muted mb-0">

            Manage discount coupons.

        </p>

    </div>


    
        href="<?= BASE_URL ?>admin/coupon-add.php"
        class="btn btn-dark"
    >

        <i class="bi bi-plus-lg"></i>

        Add Coupon

    </a>


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

                        <th>
                            Code
                        </th>

                        <th>
                            Type
                        </th>

                        <th>
                            Value
                        </th>

                        <th>
                            Min. Order
                        </th>

                        <th>
                            Usage
                        </th>

                        <th>
                            Expiry
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Actions
                        </th>

                    </tr>

                </thead>


                <tbody>


                    <?php if (
                        empty($coupons)
                    ): ?>


                        <tr>

                            <td
                                colspan="8"
                                class="text-center py-5 text-muted"
                            >

                                No coupons found.

                            </td>

                        </tr>


                    <?php else: ?>


                        <?php foreach (
                            $coupons
                            as $coupon
                        ): ?>


                            <?php
                                $isExpired =
                                    strtotime(
                                        $coupon["expiry"]
                                    ) < time();
                            ?>


                            <tr>

                                <td>
                                    <strong>
                                        <?= e($coupon["code"]) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= e(
                                        ucfirst(
                                            $coupon["discount_type"]
                                        )
                                    ) ?>
                                </td>

                                <td>
                                    <?php if (
                                        $coupon["discount_type"]
                                        === "percentage"
                                    ): ?>

                                        <?= e($coupon["value"]) ?>%

                                        <?php if (
                                            !empty(
                                                $coupon["maximum_discount"]
                                            )
                                        ): ?>

                                            <div class="text-muted small">
                                                Max ₹<?= e(
                                                    $coupon["maximum_discount"]
                                                ) ?>
                                            </div>

                                        <?php endif; ?>

                                    <?php else: ?>

                                        ₹<?= e($coupon["value"]) ?>

                                    <?php endif; ?>
                                </td>

                                <td>
                                    ₹<?= e(
                                        $coupon["minimum_amount"]
                                    ) ?>
                                </td>

                                <td>
                                    <?= e($coupon["used_count"]) ?>

                                    /

                                    <?= $coupon["usage_limit"]
                                        !== null
                                            ? e($coupon["usage_limit"])
                                            : "∞" ?>
                                </td>

                                <td>
                                    <?= e(
                                        date(
                                            "d M Y",
                                            strtotime(
                                                $coupon["expiry"]
                                            )
                                        )
                                    ) ?>

                                    <?php if ($isExpired): ?>

                                        <span class="badge bg-danger">
                                            Expired
                                        </span>

                                    <?php endif; ?>
                                </td>

                                <td>

                                    
                                        href="<?= BASE_URL ?>admin/coupon-toggle.php?id=<?= e($coupon["id"]) ?>"
                                        class="badge <?= $coupon["status"] === "active" ? "bg-success" : "bg-secondary" ?> text-decoration-none"
                                    >

                                        <?= e(
                                            ucfirst(
                                                $coupon["status"]
                                            )
                                        ) ?>

                                    </a>

                                </td>

                                <td>

                                    
                                        href="<?= BASE_URL ?>admin/coupon-delete.php?id=<?= e($coupon["id"]) ?>"
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('Delete this coupon?');"
                                    >

                                        <i class="bi bi-trash"></i>

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


<?php

require_once "includes/footer.php";