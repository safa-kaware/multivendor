<?php

require_once "config/app.php";


/*
|--------------------------------------------------------------------------
| If Already Logged In
|--------------------------------------------------------------------------
*/

if (isLoggedIn()) {

    redirect(
        BASE_URL . "profile.php"
    );

}


$pageTitle =
    "Login | "
    . APP_NAME;


$errors = [];

$email = "";


/*
|--------------------------------------------------------------------------
| Handle Login
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"]
    === "POST"
) {

    $email =
        trim(
            strtolower(
                $_POST["email"]
                ?? ""
            )
        );


    $password =
        $_POST["password"]
        ?? "";


    /*
    |--------------------------------------------------------------------------
    | Validate
    |--------------------------------------------------------------------------
    */

    if (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $errors[] =
            "Please enter a valid email address.";

    }


    if (
        $password === ""
    ) {

        $errors[] =
            "Please enter your password.";

    }


    /*
    |--------------------------------------------------------------------------
    | Login
    |--------------------------------------------------------------------------
    */

    if (
        empty($errors)
    ) {

        $result =
            loginUser(
                $pdo,
                $email,
                $password
            );


        if (
            $result["success"]
        ) {

            /*
            |--------------------------------------------------------------------------
            | Redirect According to Role
            |--------------------------------------------------------------------------
            */

            switch (
                $result["role"]
            ) {

                case "admin":

                    redirect(
                        BASE_URL
                        . "admin/dashboard.php"
                    );

                    break;


                case "vendor":

                    redirect(
                        BASE_URL
                        . "vendor/dashboard.php"
                    );

                    break;


                default:

                    redirect(
                        BASE_URL
                        . "profile.php"
                    );

                    break;

            }

        }


        $errors[] =
            $result["message"];

    }

}


require_once "includes/header.php";

?>


<main>

    <div class="container py-5">

        <div class="row justify-content-center">

            <div class="col-md-7 col-lg-5">


                <div class="card border-0 shadow-sm">

                    <div class="card-body p-4 p-md-5">


                        <h1 class="fw-bold text-center">

                            Welcome Back

                        </h1>


                        <p class="text-muted text-center mb-4">

                            Login to your account

                        </p>


                        <!-- Registration Success -->

                        <?php if (
                            !empty(
                                $_SESSION["auth_success"]
                            )
                        ): ?>

                            <div class="alert alert-success">

                                <?= e(
                                    $_SESSION["auth_success"]
                                ) ?>

                            </div>

                            <?php

                            unset(
                                $_SESSION["auth_success"]
                            );

                            ?>

                        <?php endif; ?>


                        <!-- Errors -->

                        <?php if (
                            !empty($errors)
                        ): ?>

                            <div class="alert alert-danger">

                                <ul class="mb-0">

                                    <?php foreach (
                                        $errors
                                        as $error
                                    ): ?>

                                        <li>

                                            <?= e($error) ?>

                                        </li>

                                    <?php endforeach; ?>

                                </ul>

                            </div>

                        <?php endif; ?>


                        <form
                            method="POST"
                            action="<?= BASE_URL ?>login.php"
                        >


                            <!-- Email -->

                            <div class="mb-3">

                                <label
                                    for="email"
                                    class="form-label"
                                >

                                    Email Address

                                </label>


                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    class="form-control"
                                    value="<?= e($email) ?>"
                                    required
                                >

                            </div>


                            <!-- Password -->

                            <div class="mb-4">

                                <label
                                    for="password"
                                    class="form-label"
                                >

                                    Password

                                </label>

<div class="input-group">

                                    <input
                                        type="password"
                                        id="password"
                                        name="password"
                                        class="form-control"
                                        required
                                    >

                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary"
                                        id="togglePassword"
                                        tabindex="-1"
                                    >

                                        <i class="bi bi-eye" id="togglePasswordIcon"></i>

                                    </button>

                                </div>

                            </div>

                            </div>


                            <button
                                type="submit"
                                class="btn btn-dark w-100"
                            >

                                Login

                            </button>


                        </form>


                        <p class="text-center mt-4 mb-0">

                            Don't have an account?

                            <a
                                href="<?= BASE_URL ?>signup.php"
                            >

                                Create Account

                            </a>

                        </p>


                    </div>

                </div>


            </div>

        </div>

    </div>

</main>


<?php
?>

<script>
document.getElementById("togglePassword").addEventListener("click", function () {

    const passwordInput = document.getElementById("password");
    const icon = document.getElementById("togglePasswordIcon");

    if (passwordInput.type === "password") {
        passwordInput.type = "text";
        icon.classList.remove("bi-eye");
        icon.classList.add("bi-eye-slash");
    } else {
        passwordInput.type = "password";
        icon.classList.remove("bi-eye-slash");
        icon.classList.add("bi-eye");
    }

});
</script>

<?php

require_once "includes/footer.php";

require_once "includes/footer.php";

?>