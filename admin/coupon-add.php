<?php

require_once "../config/app.php";


requireAdmin();


$error = "";


/*
|--------------------------------------------------------------------------
| Handle Form
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"]
    === "POST"
) {


    $code =
        strtoupper(
            trim(
                $_POST["code"]
                ?? ""
            )
        );


    $discountType =
        $_POST["discount_type"]
        ?? "percentage";


    $value =
        trim(
            $_POST["value"]
            ?? ""
        );


    $minimumAmount =
        trim(
            $_POST["minimum_amount"]
            ?? "0"
        );


    $maximumDiscount =
        trim(
            $_POST["maximum_discount"]
            ?? ""
        );


    $usageLimit =
        trim(
            $_POST["usage_limit"]
            ?? ""
        );


    $expiry =
        trim(
            $_POST["expiry"]
            ?? ""
        );


    $status =
        $_POST["status"]
        ?? "active";


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if (
        $code === ""
    ) {

        $error =
            "Coupon code is required.";

    } elseif (
        !in_array(
            $discountType,
            ["percentage", "fixed"]
        )
    ) {

        $error =
            "Invalid discount type.";

    } elseif (
        !is_numeric($value)
        || (float) $value <= 0
    ) {

        $error =
            "Please enter a valid discount value.";

    } elseif (
        $discountType === "percentage"
        && (float) $value > 100
    ) {

        $error =
            "Percentage discount cannot exceed 100.";

    } elseif (
        !is_numeric($minimumAmount)
        || (float) $minimumAmount < 0
    ) {

        $error =
            "Please enter a valid minimum order amount.";

    } elseif (
        $expiry === ""
        || strtotime($expiry) === false
    ) {

        $error =
            "Please enter a valid expiry date.";

    }


    /*
    |--------------------------------------------------------------------------
    | Check Duplicate Code
    |--------------------------------------------------------------------------
    */

    if (
        $error === ""
    ) {

        $stmt =
            $pdo->prepare(
                "SELECT id
                 FROM coupons
                 WHERE code = ?"
            );

        $stmt->execute([$code]);

        if (
            $stmt->fetch()
        ) {

            $error =
                "A coupon with this code already exists.";

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Insert
    |--------------------------------------------------------------------------
    */

    if (
        $error === ""
    ) {

        $stmt =
            $pdo->prepare(
                "INSERT INTO coupons

                (
                    code,
                    discount_type,
                    value,
                    minimum_amount,
                    maximum_discount,
                    usage_limit,
                    expiry,
                    status
                )

                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )"
            );


        $stmt->execute([

            $code,

            $discountType,

            (float) $value,

            (float) $minimumAmount,

            $maximumDiscount !== ""
                ? (float) $maximumDiscount
                : null,

            $usageLimit !== ""
                ? (int) $usageLimit
                : null,

            date(
                "Y-m-d H:i:s",
                strtotime($expiry)
            ),

            $status

        ]);


        redirect(
            BASE_URL
            . "admin/coupons.php"
        );

    }

}


$pageTitle =
    "Add Coupon | "
    . APP_NAME;


require_once "includes/header.php";

?>


<div class="mb-4">

    <h1 class="fw-bold">
        Add Coupon
    </h1>

    <p class="text-muted mb-0">
        Create a new discount coupon.
    </p>

</div>


<div class="card border-0 shadow-sm">

    <div class="card-body">


        <?php if ($error !== ""): ?>

            <div class="alert alert-danger">
                <?= e($error) ?>
            </div>

        <?php endif; ?>


        <form method="POST">

            <div class="row g-3">

                <div class="col-md-6">

                    <label class="form-label" for="code">
                        Coupon Code
                    </label>

                    <input
                        type="text"
                        id="code"
                        name="code"
                        class="form-control text-uppercase"
                        placeholder="e.g. SAVE20"
                        value="<?= e($_POST["code"] ?? "") ?>"
                        required
                    >

                </div>


                <div class="col-md-6">

                    <label class="form-label" for="discount_type">
                        Discount Type
                    </label>

                    <select
                        id="discount_type"
                        name="discount_type"
                        class="form-select"
                    >

                        <option value="percentage">
                            Percentage (%)
                        </option>

                        <option value="fixed">
                            Fixed Amount (₹)
                        </option>

                    </select>

                </div>


                <div class="col-md-4">

                    <label class="form-label" for="value">
                        Discount Value
                    </label>

                    <input
                        type="number"
                        step="0.01"
                        id="value"
                        name="value"
                        class="form-control"
                        value="<?= e($_POST["value"] ?? "") ?>"
                        required
                    >

                </div>


                <div class="col-md-4">

                    <label class="form-label" for="minimum_amount">
                        Minimum Order Amount (₹)
                    </label>

                    <input
                        type="number"
                        step="0.01"
                        id="minimum_amount"
                        name="minimum_amount"
                        class="form-control"
                        value="<?= e($_POST["minimum_amount"] ?? "0") ?>"
                    >

                </div>


                <div class="col-md-4">

                    <label class="form-label" for="maximum_discount">
                        Maximum Discount Cap (₹, optional)
                    </label>

                    <input
                        type="number"
                        step="0.01"
                        id="maximum_discount"
                        name="maximum_discount"
                        class="form-control"
                        placeholder="Leave blank for no cap"
                        value="<?= e($_POST["maximum_discount"] ?? "") ?>"
                    >

                </div>


                <div class="col-md-4">

                    <label class="form-label" for="usage_limit">
                        Usage Limit (optional)
                    </label>

                    <input
                        type="number"
                        id="usage_limit"
                        name="usage_limit"
                        class="form-control"
                        placeholder="Leave blank for unlimited"
                        value="<?= e($_POST["usage_limit"] ?? "") ?>"
                    >

                </div>


                <div class="col-md-4">

                    <label class="form-label" for="expiry">
                        Expiry Date
                    </label>

                    <input
                        type="date"
                        id="expiry"
                        name="expiry"
                        class="form-control"
                        value="<?= e($_POST["expiry"] ?? "") ?>"
                        required
                    >

                </div>


                <div class="col-md-4">

                    <label class="form-label" for="status">
                        Status
                    </label>

                    <select
                        id="status"
                        name="status"
                        class="form-select"
                    >

                        <option value="active">
                            Active
                        </option>

                        <option value="inactive">
                            Inactive
                        </option>

                    </select>

                </div>

            </div>


            <div class="mt-4">

                <button
                    type="submit"
                    class="btn btn-dark"
                >
                    Save Coupon
                </button>

                
                    href="<?= BASE_URL ?>admin/coupons.php"
                    class="btn btn-outline-secondary"
                >
                    Cancel
                </a>

            </div>

        </form>


    </div>

</div>


<?php

require_once "includes/footer.php";