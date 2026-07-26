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
    "Create Account | "
    . APP_NAME;


$errors = [];

$name = "";

$email = "";


/*
|--------------------------------------------------------------------------
| Handle Registration
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"]
    === "POST"
) {

    $name =
        trim(
            $_POST["name"]
            ?? ""
        );


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


    $confirmPassword =
        $_POST["confirm_password"]
        ?? "";


    /*
    |--------------------------------------------------------------------------
    | Validate Name
    |--------------------------------------------------------------------------
    */

    if ($name === "") {

        $errors[] =
            "Please enter your name.";

    }


    if (
        strlen($name)
        > 100
    ) {

        $errors[] =
            "Name cannot exceed 100 characters.";

    }


    /*
    |--------------------------------------------------------------------------
    | Validate Email
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


    /*
    |--------------------------------------------------------------------------
    | Validate Password
    |--------------------------------------------------------------------------
    */

    if (
        strlen($password)
        < 8
    ) {

        $errors[] =
            "Password must contain at least 8 characters.";

    }


    /*
    |--------------------------------------------------------------------------
    | Confirm Password
    |--------------------------------------------------------------------------
    */

    if (
        $password
        !== $confirmPassword
    ) {

        $errors[] =
            "Passwords do not match.";

    }


    /*
    |--------------------------------------------------------------------------
    | Register User
    |--------------------------------------------------------------------------
    */

    if (
        empty($errors)
    ) {

        $result =
            registerUser(
                $pdo,
                $name,
                $email,
                $password
            );


        if (
            $result["success"]
        ) {

            $_SESSION["auth_success"] =
                $result["message"];


            redirect(
                BASE_URL . "login.php"
            );

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

                            Create Account

                        </h1>


                        <p class="text-muted text-center mb-4">

                            Join <?= e(APP_NAME) ?>

                        </p>


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
                            action="<?= BASE_URL ?>signup.php"
                        >


                            <!-- Name -->

                            <div class="mb-3">

                                <label
                                    for="name"
                                    class="form-label"
                                >

                                    Full Name

                                </label>


                                <input
                                    type="text"
                                    id="name"
                                    name="name"
                                    class="form-control"
                                    value="<?= e($name) ?>"
                                    maxlength="100"
                                    required
                                >

                            </div>


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
                                    maxlength="150"
                                    required
                                >

                            </div>


                            <!-- Password -->

                            <div class="mb-3">

                                <label
                                    for="password"
                                    class="form-label"
                                >

                                    Password

                                </label>


                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    class="form-control"
                                    minlength="8"
                                    required
                                >

                                <div class="form-text">

                                    Minimum 8 characters.

                                </div>

                            </div>


                            <!-- Confirm Password -->

                            <div class="mb-4">

                                <label
                                    for="confirm_password"
                                    class="form-label"
                                >

                                    Confirm Password

                                </label>


                                <input
                                    type="password"
                                    id="confirm_password"
                                    name="confirm_password"
                                    class="form-control"
                                    minlength="8"
                                    required
                                >

                            </div>


                            <button
                                type="submit"
                                class="btn btn-dark w-100"
                            >

                                Create Account

                            </button>


                        </form>


                        <p class="text-center mt-4 mb-0">

                            Already have an account?

                            <a
                                href="<?= BASE_URL ?>login.php"
                            >

                                Login

                            </a>

                        </p>


                    </div>

                </div>


            </div>

        </div>

    </div>

</main>


<?php

require_once "includes/footer.php";

?>