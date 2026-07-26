<?php

require_once "../config/app.php";

requireAdmin();

$pageTitle = "Manage Users | " . APP_NAME;


/*
|--------------------------------------------------------------------------
| FETCH USERS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query(
    "SELECT
        id,
        name,
        email,
        role,
        status,
        created_at

     FROM users

     ORDER BY created_at DESC"
);

$users = $stmt->fetchAll();

?>


<?php require_once "includes/header.php"; ?>


<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h1 class="fw-bold">
            Users
        </h1>

        <p class="text-muted mb-0">
            Manage customer, vendor, and administrator accounts.
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

                        <th>Name</th>

                        <th>Email</th>

                        <th>Role</th>

                        <th>Status</th>

                        <th>Created</th>

                        <th>Actions</th>

                    </tr>

                </thead>


                <tbody>


                <?php if (empty($users)): ?>


                    <tr>

                        <td
                            colspan="7"
                            class="text-center py-5 text-muted"
                        >

                            No users found.

                        </td>

                    </tr>


                <?php else: ?>


                    <?php foreach (
                        $users
                        as $user
                    ): ?>


                        <tr>


                            <!-- ID -->


                            <td>

                                <?= (int)
                                    $user["id"]
                                ?>

                            </td>


                            <!-- NAME -->


                            <td>

                                <strong>

                                    <?= e(
                                        $user["name"]
                                    ) ?>

                                </strong>

                            </td>


                            <!-- EMAIL -->


                            <td>

                                <?= e(
                                    $user["email"]
                                ) ?>

                            </td>


                            <!-- ROLE -->


                            <td>


                                <?php if (
                                    $user["role"]
                                    === "admin"
                                ): ?>


                                    <span
                                        class="badge text-bg-dark"
                                    >

                                        Admin

                                    </span>


                                <?php elseif (
                                    $user["role"]
                                    === "vendor"
                                ): ?>


                                    <span
                                        class="badge text-bg-primary"
                                    >

                                        Vendor

                                    </span>


                                <?php else: ?>


                                    <span
                                        class="badge text-bg-secondary"
                                    >

                                        Customer

                                    </span>


                                <?php endif; ?>


                            </td>


                            <!-- STATUS -->


                            <td>


                                <?php if (
                                    $user["status"]
                                    === "active"
                                ): ?>


                                    <span
                                        class="badge text-bg-success"
                                    >

                                        Active

                                    </span>


                                <?php elseif (
                                    $user["status"]
                                    === "blocked"
                                ): ?>


                                    <span
                                        class="badge text-bg-danger"
                                    >

                                        Blocked

                                    </span>


                                <?php else: ?>


                                    <span
                                        class="badge text-bg-secondary"
                                    >

                                        Inactive

                                    </span>


                                <?php endif; ?>


                            </td>


                            <!-- CREATED -->


                            <td>

                                <?= date(
                                    "d M Y",
                                    strtotime(
                                        $user["created_at"]
                                    )
                                ) ?>

                            </td>


                            <!-- ACTIONS -->


                            <td>


                                <?php
                                /*
                                |--------------------------------------------------------------------------
                                | NEVER ALLOW ADMIN TO BLOCK THEMSELVES
                                |--------------------------------------------------------------------------
                                */

                                $isCurrentAdmin =
                                    (
                                        (int)
                                        $user["id"]
                                        ===
                                        (int)
                                        currentUserId()
                                    )
                                    &&
                                    $user["role"]
                                    === "admin";
                                ?>


                                <?php if (
                                    !$isCurrentAdmin
                                ): ?>


                                    <?php if (
                                        $user["status"]
                                        === "active"
                                    ): ?>


                                        <a
                                            href="<?= BASE_URL ?>admin/user-status.php?id=<?= (int) $user["id"] ?>&status=blocked"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Block this user?');"
                                        >

                                            <i
                                                class="bi bi-person-slash"
                                            ></i>

                                            Block

                                        </a>


                                    <?php else: ?>


                                        <a
                                            href="<?= BASE_URL ?>admin/user-status.php?id=<?= (int) $user["id"] ?>&status=active"
                                            class="btn btn-sm btn-outline-success"
                                            onclick="return confirm('Activate this user?');"
                                        >

                                            <i
                                                class="bi bi-person-check"
                                            ></i>

                                            Activate

                                        </a>


                                    <?php endif; ?>


                                <?php else: ?>


                                    <span
                                        class="text-muted small"
                                    >

                                        Current Admin

                                    </span>


                                <?php endif; ?>


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