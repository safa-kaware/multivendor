<?php

require_once "../config/app.php";

requireAdmin();

$pageTitle = "Manage Reviews | " . APP_NAME;


/*
|--------------------------------------------------------------------------
| FETCH ALL REVIEWS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query(
    "SELECT
        r.id,
        r.product_id,
        r.user_id,
        r.rating,
        r.comment,
        r.status,
        r.created_at,

        p.name AS product_name,

        u.name AS user_name,
        u.email AS user_email

     FROM reviews r

     INNER JOIN products p
        ON p.id = r.product_id

     INNER JOIN users u
        ON u.id = r.user_id

     ORDER BY r.created_at DESC"
);

$reviews = $stmt->fetchAll();

?>


<?php require_once "includes/header.php"; ?>


<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h1 class="fw-bold">

            Reviews

        </h1>

        <p class="text-muted mb-0">

            Review and moderate customer product reviews.

        </p>

    </div>

</div>


<div class="card border-0 shadow-sm">

    <div class="card-body">

        <div class="table-responsive">

            <table class="table table-hover align-middle">

                <thead>

                    <tr>

                        <th>ID</th>

                        <th>Product</th>

                        <th>Customer</th>

                        <th>Rating</th>

                        <th>Comment</th>

                        <th>Status</th>

                        <th>Date</th>

                        <th>Actions</th>

                    </tr>

                </thead>


                <tbody>


                <?php if (empty($reviews)): ?>


                    <tr>

                        <td
                            colspan="8"
                            class="text-center py-5 text-muted"
                        >

                            No reviews found.

                        </td>

                    </tr>


                <?php else: ?>


                    <?php foreach (
                        $reviews
                        as $review
                    ): ?>


                        <tr>


                            <!-- ID -->


                            <td>

                                <?= (int)
                                    $review["id"]
                                ?>

                            </td>


                            <!-- PRODUCT -->


                            <td>

                                <strong>

                                    <?= e(
                                        $review["product_name"]
                                    ) ?>

                                </strong>

                            </td>


                            <!-- CUSTOMER -->


                            <td>

                                <?= e(
                                    $review["user_name"]
                                ) ?>


                                <small
                                    class="d-block text-muted"
                                >

                                    <?= e(
                                        $review["user_email"]
                                    ) ?>

                                </small>

                            </td>


                            <!-- RATING -->


                            <td>

                                <span
                                    class="text-warning"
                                >

                                    <?php for (
                                        $i = 1;
                                        $i <= 5;
                                        $i++
                                    ): ?>

                                        <?= $i <=
                                            (int)
                                            $review["rating"]

                                            ? "★"
                                            : "☆"
                                        ?>

                                    <?php endfor; ?>

                                </span>


                                <small
                                    class="text-muted"
                                >

                                    (<?= (int)
                                        $review["rating"]
                                    ?>/5)

                                </small>

                            </td>


                            <!-- COMMENT -->


                            <td>

                                <?= e(
                                    mb_strimwidth(
                                        $review["comment"],
                                        0,
                                        80,
                                        "..."
                                    )
                                ) ?>

                            </td>


                            <!-- STATUS -->


                            <td>


                                <?php if (
                                    $review["status"]
                                    === "approved"
                                ): ?>


                                    <span
                                        class="badge text-bg-success"
                                    >

                                        Approved

                                    </span>


                                <?php elseif (
                                    $review["status"]
                                    === "pending"
                                ): ?>


                                    <span
                                        class="badge text-bg-warning"
                                    >

                                        Pending

                                    </span>


                                <?php else: ?>


                                    <span
                                        class="badge text-bg-danger"
                                    >

                                        Rejected

                                    </span>


                                <?php endif; ?>


                            </td>


                            <!-- DATE -->


                            <td>

                                <?= date(
                                    "d M Y",
                                    strtotime(
                                        $review["created_at"]
                                    )
                                ) ?>

                            </td>


                            <!-- ACTIONS -->


                            <td>


                                <div
                                    class="d-flex gap-2 flex-wrap"
                                >


                                    <?php if (
                                        $review["status"]
                                        !== "approved"
                                    ): ?>


                                        <a
                                            href="<?= BASE_URL ?>admin/review-approve.php?id=<?= (int) $review["id"] ?>"
                                            class="btn btn-sm btn-outline-success"
                                            onclick="return confirm('Approve this review?');"
                                        >

                                            <i
                                                class="bi bi-check-lg"
                                            ></i>

                                            Approve

                                        </a>


                                    <?php endif; ?>


                                    <?php if (
                                        $review["status"]
                                        !== "rejected"
                                    ): ?>


                                        <a
                                            href="<?= BASE_URL ?>admin/review-reject.php?id=<?= (int) $review["id"] ?>"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Reject this review?');"
                                        >

                                            <i
                                                class="bi bi-x-lg"
                                            ></i>

                                            Reject

                                        </a>


                                    <?php endif; ?>


                                    <a
                                        href="<?= BASE_URL ?>admin/review-delete.php?id=<?= (int) $review["id"] ?>"
                                        class="btn btn-sm btn-outline-dark"
                                        onclick="return confirm('Delete this review permanently?');"
                                    >

                                        <i
                                            class="bi bi-trash"
                                        ></i>

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


<?php require_once "includes/footer.php"; ?>