<?php

require_once "config/app.php";


requireCustomer();


$orderId =
    $_SESSION["order_success_id"]
    ?? null;


if (
    !$orderId
) {

    redirect(
        BASE_URL . "profile.php"
    );

}


unset(
    $_SESSION["order_success_id"]
);


$pageTitle =
    "Order Confirmed | "
    . APP_NAME;


require_once "includes/header.php";

?>


<main>

    <div class="container py-5">


        <div class="row justify-content-center">

            <div class="col-md-7">


                <div class="card border-0 shadow-sm text-center">

                    <div class="card-body p-5">


                        <div
                            class="text-success mb-4"
                        >

                            <i
                                class="bi bi-check-circle-fill"
                                style="font-size: 70px;"
                            ></i>

                        </div>


                        <h1 class="fw-bold">

                            Order Placed Successfully!

                        </h1>


                        <p class="text-muted mt-3">

                            Thank you for your purchase.

                        </p>


                        <p>

                            Your Order ID is:

                            <strong>

                                #<?= e(
                                    $orderId
                                ) ?>

                            </strong>

                        </p>


                        <div class="d-flex justify-content-center gap-2 mt-4">


                            <a
                                href="<?= BASE_URL ?>profile.php"
                                class="btn btn-dark"
                            >

                                View Profile

                            </a>


                            <a
                                href="<?= BASE_URL ?>search.php"
                                class="btn btn-outline-dark"
                            >

                                Continue Shopping

                            </a>


                        </div>


                    </div>

                </div>


            </div>

        </div>


    </div>

</main>


<?php

require_once "includes/footer.php";

?>