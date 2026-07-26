<?php

require_once "config/app.php";


/*
|--------------------------------------------------------------------------
| Require Customer Login
|--------------------------------------------------------------------------
*/

$userId = currentUserId();

$orders = getUserOrders(
    $pdo,
    $userId
);


/*
|--------------------------------------------------------------------------
| Get User
|--------------------------------------------------------------------------
*/

$userId =
    currentUserId();


$stmt = $pdo->prepare(
    "SELECT
        id,
        name,
        email,
        role,
        status,
        created_at

     FROM users

     WHERE id = ?

     LIMIT 1"
);


$stmt->execute([
    $userId
]);


$user =
    $stmt->fetch();


if (!$user) {

    logoutUser();

    redirect(
        BASE_URL . "login.php"
    );

}


$pageTitle =
    "My Profile | "
    . APP_NAME;


require_once "includes/header.php";

?>


<main>

    <div class="container py-5">


        <h1 class="fw-bold mb-4">

            My Profile

        </h1>


        <div class="row g-4">


            <!-- Profile -->

            <div class="col-lg-5">


                <div class="card border-0 shadow-sm">

                    <div class="card-body p-4">


                        <div class="text-center mb-4">

                            <div
                                class="rounded-circle bg-dark text-white d-inline-flex align-items-center justify-content-center"
                                style="width: 80px; height: 80px;"
                            >

                                <i
                                    class="bi bi-person fs-1"
                                ></i>

                            </div>

                        </div>


                        <h3 class="text-center">

                            <?= e(
                                $user["name"]
                            ) ?>

                        </h3>


                        <p class="text-muted text-center">

                            <?= e(
                                $user["email"]
                            ) ?>

                        </p>


                        <hr>


                        <div class="mb-3">

                            <strong>

                                Account Status

                            </strong>

                            <br>

                            <span class="badge text-bg-success">

                                <?= e(
                                    ucfirst(
                                        $user["status"]
                                    )
                                ) ?>

                            </span>

                        </div>


                        <div>

                            <strong>

                                Member Since

                            </strong>

                            <br>

                            <span class="text-muted">

                                <?= date(
                                    "d M Y",
                                    strtotime(
                                        $user["created_at"]
                                    )
                                ) ?>

                            </span>

                        </div>


                    </div>

                </div>


            </div>


            <!-- Quick Links -->

            <div class="col-lg-7">


                <div class="row g-3">


                    
<div class="card border-0 shadow-sm mt-4">

    <div class="card-body p-4">

        <h3 class="fw-bold mb-4">

            <i class="bi bi-box-seam"></i>

            My Orders

        </h3>


        <?php if (empty($orders)): ?>

            <div class="text-center py-4">

                <i
                    class="bi bi-bag-x"
                    style="font-size: 50px;"
                ></i>

                <p class="mt-3 text-muted">

                    You haven't placed any orders yet.

                </p>

                <a
                    href="<?= BASE_URL ?>search.php"
                    class="btn btn-dark"
                >

                    Start Shopping

                </a>

            </div>


        <?php else: ?>


            <div class="table-responsive">

                <table class="table align-middle">

                    <thead>

                        <tr>

                            <th>
                                Order
                            </th>

                            <th>
                                Date
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
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <?php foreach (
                            $orders
                            as $order
                        ): ?>


                            <tr>


                                <td>

                                    <strong>

                                        #<?= e(
                                            $order["id"]
                                        ) ?>

                                    </strong>

                                </td>


                                <td>

                                    <?= date(
                                        "d M Y",
                                        strtotime(
                                            $order["created_at"]
                                        )
                                    ) ?>

                                </td>


                                <td>

                                    ₹<?= number_format(
                                        $order["total_amount"],
                                        2
                                    ) ?>

                                </td>


                                <td>

                                    <span
                                        class="badge text-bg-secondary"
                                    >

                                        <?= e(
                                            strtoupper(
                                                $order["payment_status"]
                                            )
                                        ) ?>

                                    </span>

                                </td>


                                <td>

                                    <?php

                                    $statusClass =
                                        "text-bg-secondary";


                                    if (
                                        $order["order_status"]
                                        === "pending"
                                    ) {

                                        $statusClass =
                                            "text-bg-warning";

                                    }


                                    if (
                                        $order["order_status"]
                                        === "processing"
                                    ) {

                                        $statusClass =
                                            "text-bg-info";

                                    }


                                    if (
                                        $order["order_status"]
                                        === "shipped"
                                    ) {

                                        $statusClass =
                                            "text-bg-primary";

                                    }


                                    if (
                                        $order["order_status"]
                                        === "delivered"
                                    ) {

                                        $statusClass =
                                            "text-bg-success";

                                    }


                                    if (
                                        $order["order_status"]
                                        === "cancelled"
                                    ) {

                                        $statusClass =
                                            "text-bg-danger";

                                    }

                                    ?>


                                    <span
                                        class="badge <?= $statusClass ?>"
                                    >

                                        <?= e(
                                            ucfirst(
                                                $order["order_status"]
                                            )
                                        ) ?>

                                    </span>


                                </td>


                                <td>

                                    <a
                                        href="<?= BASE_URL ?>order-details.php?id=<?= $order["id"] ?>"
                                        class="btn btn-sm btn-outline-dark"
                                    >

                                        View Details

                                    </a>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                    </tbody>

                </table>

            </div>


        <?php endif; ?>


    </div>

</div>

                    <div class="col-md-6">

                        <a
                            href="<?= BASE_URL ?>cart.php"
                            class="text-decoration-none"
                        >

                            <div class="card border-0 shadow-sm h-100">

                                <div class="card-body p-4">

                                    <i
                                        class="bi bi-cart3 fs-1"
                                    ></i>

                                    <h5 class="mt-3">

                                        My Cart

                                    </h5>

                                    <p class="text-muted mb-0">

                                        View items in your cart.

                                    </p>

                                </div>

                            </div>

                        </a>

                    </div>
                    <div class="col-md-6">

    <a
        href="<?= BASE_URL ?>addresses.php"
        class="text-decoration-none"
    >

        <div class="card border-0 shadow-sm h-100">

            <div class="card-body p-4">

                <i
                    class="bi bi-geo-alt fs-1"
                ></i>

                <h5 class="mt-3">

                    My Addresses

                </h5>

                <p class="text-muted mb-0">

                    Manage your delivery addresses.

                </p>

            </div>

        </div>

    </a>

</div>/


                    <div class="col-md-6">

                        <a
                            href="<?= BASE_URL ?>search.php"
                            class="text-decoration-none"
                        >

                            <div class="card border-0 shadow-sm h-100">

                                <div class="card-body p-4">

                                    <i
                                        class="bi bi-shop fs-1"
                                    ></i>

                                    <h5 class="mt-3">

                                        Continue Shopping

                                    </h5>

                                    <p class="text-muted mb-0">

                                        Browse our products.

                                    </p>

                                </div>

                            </div>

                        </a>

                    </div>


                    <div class="col-md-6">

                        <a
                            href="<?= BASE_URL ?>logout.php"
                            class="text-decoration-none"
                        >

                            <div class="card border-0 shadow-sm h-100">

                                <div class="card-body p-4">

                                    <i
                                        class="bi bi-box-arrow-right fs-1"
                                    ></i>

                                    <h5 class="mt-3">

                                        Logout

                                    </h5>

                                    <p class="text-muted mb-0">

                                        Sign out of your account.

                                    </p>

                                </div>

                            </div>

                        </a>

                    </div>


                </div>


            </div>


        </div>


    </div>

</main>


<?php

require_once "includes/footer.php";

?>