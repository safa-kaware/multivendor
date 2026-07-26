<?php

require_once "config/app.php";


/*
|--------------------------------------------------------------------------
| Require Customer Login
|--------------------------------------------------------------------------
*/

requireCustomer();


$userId =
    currentUserId();


$errors = [];


/*
|--------------------------------------------------------------------------
| Handle Add Address
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"]
    === "POST"
) {

    $fullName =
        trim(
            $_POST["full_name"]
            ?? ""
        );


    $phone =
        trim(
            $_POST["phone"]
            ?? ""
        );


    $addressLine1 =
        trim(
            $_POST["address_line1"]
            ?? ""
        );


    $addressLine2 =
        trim(
            $_POST["address_line2"]
            ?? ""
        );


    $city =
        trim(
            $_POST["city"]
            ?? ""
        );


    $state =
        trim(
            $_POST["state"]
            ?? ""
        );


    $postalCode =
        trim(
            $_POST["postal_code"]
            ?? ""
        );


    $country =
        trim(
            $_POST["country"]
            ?? "India"
        );


    $addressType =
        $_POST["address_type"]
        ?? "home";


    $isDefault =
        isset(
            $_POST["is_default"]
        )
        ? 1
        : 0;


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if (
        $fullName === ""
    ) {

        $errors[] =
            "Full name is required.";

    }


    if (
        $phone === ""
        || !preg_match(
            "/^[0-9+\-\s]{10,20}$/",
            $phone
        )
    ) {

        $errors[] =
            "Please enter a valid phone number.";

    }


    if (
        $addressLine1 === ""
    ) {

        $errors[] =
            "Address is required.";

    }


    if (
        $city === ""
    ) {

        $errors[] =
            "City is required.";

    }


    if (
        $state === ""
    ) {

        $errors[] =
            "State is required.";

    }


    if (
        $postalCode === ""
    ) {

        $errors[] =
            "Postal code is required.";

    }


    if (
        !in_array(
            $addressType,
            [
                "home",
                "work",
                "other"
            ],
            true
        )
    ) {

        $errors[] =
            "Invalid address type.";

    }


    /*
    |--------------------------------------------------------------------------
    | Save Address
    |--------------------------------------------------------------------------
    */

    if (
        empty($errors)
    ) {

        /*
        |--------------------------------------------------------------------------
        | If First Address
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare(
            "SELECT COUNT(*)
             FROM addresses
             WHERE user_id = ?"
        );


        $stmt->execute([
            $userId
        ]);


        $addressCount =
            (int) $stmt->fetchColumn();


        if (
            $addressCount === 0
        ) {

            $isDefault = 1;

        }


        /*
        |--------------------------------------------------------------------------
        | Remove Existing Default
        |--------------------------------------------------------------------------
        */

        if (
            $isDefault === 1
        ) {

            $stmt = $pdo->prepare(
                "UPDATE addresses

                 SET is_default = 0

                 WHERE user_id = ?"
            );


            $stmt->execute([
                $userId
            ]);

        }


        /*
        |--------------------------------------------------------------------------
        | Insert Address
        |--------------------------------------------------------------------------
        */
        

        $stmt = $pdo->prepare(
            "INSERT INTO addresses
            (
                user_id,
                full_name,
                phone,
                address_line,
                city,
                state,
                pincode,
                country,
                is_default
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $userId,
            $fullName,
            $phone,
            $address_Line,
            $city,
            $state,
            $pincode,
            $country,
            $isDefault
        ]);


        $_SESSION["address_success"] =
            "Address added successfully.";


        redirect(
            BASE_URL . "addresses.php"
        );

    }

}


/*
|--------------------------------------------------------------------------
| Get User Addresses
|--------------------------------------------------------------------------
*/

$addresses =
    getUserAddresses(
        $pdo,
        $userId
    );


$pageTitle =
    "My Addresses | "
    . APP_NAME;


require_once "includes/header.php";

?>


<main>

    <div class="container py-5">


        <div class="d-flex justify-content-between align-items-center mb-4">

            <h1 class="fw-bold mb-0">

                My Addresses

            </h1>


            <a
                href="<?= BASE_URL ?>profile.php"
                class="btn btn-outline-dark"
            >

                <i class="bi bi-arrow-left"></i>

                Profile

            </a>

        </div>


        <!-- Success -->

        <?php if (
            !empty(
                $_SESSION["address_success"]
            )
        ): ?>

            <div class="alert alert-success">

                <?= e(
                    $_SESSION["address_success"]
                ) ?>

            </div>

            <?php

            unset(
                $_SESSION["address_success"]
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


        <div class="row g-4">


            <!-- Add Address -->

            <div class="col-lg-5">


                <div class="card border-0 shadow-sm">

                    <div class="card-body p-4">


                        <h4 class="fw-bold mb-4">

                            Add New Address

                        </h4>


                        <form
                            method="POST"
                            action="<?= BASE_URL ?>addresses.php"
                        >


                            <!-- Name -->

                            <div class="mb-3">

                                <label
                                    class="form-label"
                                >

                                    Full Name

                                </label>


                                <input
                                    type="text"
                                    name="full_name"
                                    class="form-control"
                                    maxlength="100"
                                    required
                                >

                            </div>


                            <!-- Phone -->

                            <div class="mb-3">

                                <label
                                    class="form-label"
                                >

                                    Phone Number

                                </label>


                                <input
                                    type="tel"
                                    name="phone"
                                    class="form-control"
                                    maxlength="20"
                                    required
                                >

                            </div>


                            <!-- Address -->

                            <div class="mb-3">

                                <label
                                    class="form-label"
                                >

                                    Address Line 1

                                </label>


                                <input
                                    type="text"
                                    name="address_line1"
                                    class="form-control"
                                    maxlength="255"
                                    required
                                >

                            </div>


                            <!-- Address 2 -->

                            <div class="mb-3">

                                <label
                                    class="form-label"
                                >

                                    Address Line 2

                                    <span class="text-muted">

                                        (Optional)

                                    </span>

                                </label>


                                <input
                                    type="text"
                                    name="address_line2"
                                    class="form-control"
                                    maxlength="255"
                                >

                            </div>


                            <div class="row">


                                <!-- City -->

                                <div class="col-md-6 mb-3">

                                    <label
                                        class="form-label"
                                    >

                                        City

                                    </label>


                                    <input
                                        type="text"
                                        name="city"
                                        class="form-control"
                                        required
                                    >

                                </div>


                                <!-- State -->

                                <div class="col-md-6 mb-3">

                                    <label
                                        class="form-label"
                                    >

                                        State

                                    </label>


                                    <input
                                        type="text"
                                        name="state"
                                        class="form-control"
                                        required
                                    >

                                </div>


                            </div>


                            <div class="row">


                                <!-- Postal Code -->

                                <div class="col-md-6 mb-3">

                                    <label
                                        class="form-label"
                                    >

                                        Postal Code

                                    </label>


                                    <input
                                        type="text"
                                        name="postal_code"
                                        class="form-control"
                                        required
                                    >

                                </div>


                                <!-- Country -->

                                <div class="col-md-6 mb-3">

                                    <label
                                        class="form-label"
                                    >

                                        Country

                                    </label>


                                    <input
                                        type="text"
                                        name="country"
                                        class="form-control"
                                        value="India"
                                        required
                                    >

                                </div>


                            </div>


                            <!-- Address Type -->

                            <div class="mb-3">

                                <label
                                    class="form-label"
                                >

                                    Address Type

                                </label>


                                <select
                                    name="address_type"
                                    class="form-select"
                                >

                                    <option value="home">

                                        Home

                                    </option>


                                    <option value="work">

                                        Work

                                    </option>


                                    <option value="other">

                                        Other

                                    </option>

                                </select>

                            </div>


                            <!-- Default -->

                            <div class="form-check mb-4">

                                <input
                                    type="checkbox"
                                    name="is_default"
                                    value="1"
                                    class="form-check-input"
                                    id="is_default"
                                >


                                <label
                                    class="form-check-label"
                                    for="is_default"
                                >

                                    Set as default address

                                </label>

                            </div>


                            <button
                                type="submit"
                                class="btn btn-dark w-100"
                            >

                                <i class="bi bi-plus-circle"></i>

                                Add Address

                            </button>


                        </form>


                    </div>

                </div>


            </div>


            <!-- Existing Addresses -->

            <div class="col-lg-7">


                <h4 class="fw-bold mb-3">

                    Saved Addresses

                </h4>


                <?php if (
                    empty($addresses)
                ): ?>


                    <div class="card border-0 shadow-sm">

                        <div class="card-body text-center py-5">

                            <i
                                class="bi bi-geo-alt display-4 text-muted"
                            ></i>


                            <p class="text-muted mt-3 mb-0">

                                You haven't added any addresses yet.

                            </p>

                        </div>

                    </div>


                <?php else: ?>


                    <?php foreach (
                        $addresses
                        as $address
                    ): ?>


                        <div class="card border-0 shadow-sm mb-3">

                            <div class="card-body">


                                <div class="d-flex justify-content-between">

                                    <div>


                                        <h5 class="fw-bold">

                                            <?= e(
                                                $address["full_name"]
                                            ) ?>


                                            <?php if (
                                                $address["is_default"]
                                            ): ?>

                                                <span class="badge text-bg-success">

                                                    Default

                                                </span>

                                            <?php endif; ?>


                                        </h5>


                                        <p class="mb-1">

                                            <?= e(
                                                $address["phone"]
                                            ) ?>

                                        </p>


                                        <p class="mb-1">

                                            <?= e(
                                                $address["address_line1"]
                                            ) ?>

                                        </p>


                                        <?php if (
                                            !empty(
                                                $address["address_line2"]
                                            )
                                        ): ?>

                                            <p class="mb-1">

                                                <?= e(
                                                    $address["address_line2"]
                                                ) ?>

                                            </p>

                                        <?php endif; ?>


                                        <p class="mb-0">

                                            <?= e(
                                                $address["city"]
                                            ) ?>,

                                            <?= e(
                                                $address["state"]
                                            ) ?>

                                            -

                                            <?= e(
                                                $address["postal_code"]
                                            ) ?>


                                            <br>

                                            <?= e(
                                                $address["country"]
                                            ) ?>

                                        </p>


                                    </div>


                                    <span class="badge text-bg-light align-self-start">

                                        <?= e(
                                            ucfirst(
                                                $address["address_type"]
                                            )
                                        ) ?>

                                    </span>


                                </div>


                            </div>

                        </div>


                    <?php endforeach; ?>


                <?php endif; ?>


            </div>


        </div>


    </div>

</main>


<?php

require_once "includes/footer.php";

?>