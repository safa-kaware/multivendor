<?php

require_once "../config/app.php";

requireVendor($pdo);

$vendorId = currentVendorId($pdo);

if (!$vendorId) {
    die("Vendor account not found.");
}


/*
|--------------------------------------------------------------------------
| GET PRODUCT ID
|--------------------------------------------------------------------------
*/

$productId = isset($_GET["id"])
    ? (int) $_GET["id"]
    : 0;

if ($productId <= 0) {
    die("Invalid product ID.");
}


/*
|--------------------------------------------------------------------------
| FETCH PRODUCT
|--------------------------------------------------------------------------
|
| Vendor can only manage variations
| for their own product.
|
*/

$stmt = $pdo->prepare(
    "SELECT id, name
     FROM products
     WHERE id = ?
     AND vendor_id = ?
     LIMIT 1"
);

$stmt->execute([
    $productId,
    $vendorId
]);

$product = $stmt->fetch();

if (!$product) {

    http_response_code(404);

    die(
        "Product not found or you do not have permission to manage its variations."
    );

}


/*
|--------------------------------------------------------------------------
| ADD VARIATION
|--------------------------------------------------------------------------
*/

$errors = [];

$attribute = "";
$value = "";
$stock = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action =
        $_POST["action"]
        ?? "";


    /*
    |--------------------------------------------------------------------------
    | ADD VARIATION
    |--------------------------------------------------------------------------
    */

    if ($action === "add") {

        $attribute =
            trim(
                $_POST["attribute"]
                ?? ""
            );

        $value =
            trim(
                $_POST["value"]
                ?? ""
            );

        $stock =
            trim(
                $_POST["stock"]
                ?? ""
            );


        /*
        |----------------------------------------------------------------------
        | VALIDATION
        |----------------------------------------------------------------------
        */

        if (
            $attribute === ""
        ) {

            $errors[] =
                "Attribute name is required.";

        }


        if (
            $value === ""
        ) {

            $errors[] =
                "Variation value is required.";

        }


        if (
            $stock === ""
            ||
            !ctype_digit(
                $stock
            )
        ) {

            $errors[] =
                "Please enter a valid stock quantity.";

        }


        /*
        |--------------------------------------------------------------------------
        | CHECK DUPLICATE VARIATION
        |--------------------------------------------------------------------------
        */

        if (
            empty($errors)
        ) {

            $stmt = $pdo->prepare(
                "SELECT id
                 FROM product_variations
                 WHERE product_id = ?
                 AND attribute = ?
                 AND value = ?
                 LIMIT 1"
            );

            $stmt->execute([

                $productId,

                $attribute,

                $value

            ]);


            if (
                $stmt->fetch()
            ) {

                $errors[] =
                    "This variation already exists.";

            }

        }


        /*
        |--------------------------------------------------------------------------
        | INSERT VARIATION
        |--------------------------------------------------------------------------
        */

        if (
            empty($errors)
        ) {

            try {

                $stmt = $pdo->prepare(
                    "INSERT INTO product_variations
                    (
                        product_id,
                        attribute,
                        value,
                        stock
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?
                    )"
                );


                $stmt->execute([

                    $productId,

                    $attribute,

                    $value,

                    (int) $stock

                ]);


                redirect(
                    BASE_URL
                    . "vendor/product-variations.php?id="
                    . $productId
                );


            } catch (
                PDOException $e
            ) {

                $errors[] =
                    "Failed to add variation.";

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | DELETE VARIATION
    |--------------------------------------------------------------------------
    */

    if ($action === "delete") {

        $variationId =
            isset(
                $_POST["variation_id"]
            )
                ? (int)
                    $_POST["variation_id"]
                : 0;


        if (
            $variationId <= 0
        ) {

            $errors[] =
                "Invalid variation.";

        }


        if (
            empty($errors)
        ) {

            try {

                /*
                |--------------------------------------------------------------------------
                | SECURITY CHECK
                |--------------------------------------------------------------------------
                |
                | The variation must belong to
                | the selected product, and the
                | selected product must belong
                | to the current vendor.
                |
                */

                $stmt = $pdo->prepare(
                    "DELETE pv

                     FROM product_variations pv

                     INNER JOIN products p
                        ON pv.product_id = p.id

                     WHERE pv.id = ?

                     AND pv.product_id = ?

                     AND p.vendor_id = ?"
                );


                $stmt->execute([

                    $variationId,

                    $productId,

                    $vendorId

                ]);


                redirect(
                    BASE_URL
                    . "vendor/product-variations.php?id="
                    . $productId
                );


            } catch (
                PDOException $e
            ) {

                $errors[] =
                    "Failed to delete variation.";

            }

        }

    }

}


/*
|--------------------------------------------------------------------------
| FETCH VARIATIONS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT id, attribute, value, stock
     FROM product_variations
     WHERE product_id = ?
     ORDER BY attribute ASC, value ASC"
);

$stmt->execute([
    $productId
]);

$variations =
    $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| PAGE
|--------------------------------------------------------------------------
*/

$pageTitle =
    "Product Variations | "
    . APP_NAME;

require_once "../includes/header.php";

?>


<div class="container py-5">


    <!-- PAGE HEADER -->

    <div class="mb-4">

        <a
            href="products.php"
            class="btn btn-secondary btn-sm mb-3"
        >

            ← Back to Products

        </a>


        <h2 class="fw-bold">

            Product Variations

        </h2>


        <p class="text-muted">

            Product:
            <strong>
                <?= e(
                    $product["name"]
                ) ?>
            </strong>

        </p>

    </div>



    <!-- ERROR MESSAGES -->

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

                        <?= e(
                            $error
                        ) ?>

                    </li>

                <?php endforeach; ?>

            </ul>

        </div>

    <?php endif; ?>



    <div class="row">


        <!-- ADD VARIATION -->

        <div class="col-lg-5 mb-4">

            <div class="card shadow-sm">

                <div class="card-body p-4">

                    <h5 class="fw-bold mb-4">

                        Add Variation

                    </h5>


                    <form
                        method="POST"
                    >


                        <input
                            type="hidden"
                            name="action"
                            value="add"
                        >


                        <!-- ATTRIBUTE -->

                        <div class="mb-3">

                            <label
                                class="form-label"
                            >

                                Attribute

                            </label>


                            <input
                                type="text"
                                name="attribute"
                                class="form-control"
                                placeholder="Example: Size"
                                value="<?= e(
                                    $attribute
                                ) ?>"
                                required
                            >

                            <small
                                class="text-muted"
                            >

                                Example:
                                Size, Color, Storage

                            </small>

                        </div>



                        <!-- VALUE -->

                        <div class="mb-3">

                            <label
                                class="form-label"
                            >

                                Value

                            </label>


                            <input
                                type="text"
                                name="value"
                                class="form-control"
                                placeholder="Example: Medium"
                                value="<?= e(
                                    $value
                                ) ?>"
                                required
                            >

                        </div>



                        <!-- STOCK -->

                        <div class="mb-4">

                            <label
                                class="form-label"
                            >

                                Stock

                            </label>


                            <input
                                type="number"
                                name="stock"
                                class="form-control"
                                min="0"
                                placeholder="Example: 10"
                                value="<?= e(
                                    $stock
                                ) ?>"
                                required
                            >

                        </div>



                        <button
                            type="submit"
                            class="btn btn-primary w-100"
                        >

                            Add Variation

                        </button>


                    </form>

                </div>

            </div>

        </div>



        <!-- EXISTING VARIATIONS -->

        <div class="col-lg-7">

            <div class="card shadow-sm">

                <div class="card-body p-4">

                    <h5 class="fw-bold mb-4">

                        Existing Variations

                    </h5>


                    <?php if (
                        empty(
                            $variations
                        )
                    ): ?>

                        <div
                            class="alert alert-info"
                        >

                            No variations added yet.

                        </div>

                    <?php else: ?>


                        <div
                            class="table-responsive"
                        >

                            <table
                                class="table table-bordered align-middle"
                            >

                                <thead>

                                    <tr>

                                        <th>
                                            Attribute
                                        </th>

                                        <th>
                                            Value
                                        </th>

                                        <th>
                                            Stock
                                        </th>

                                        <th>
                                            Action
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>

                                    <?php foreach (
                                        $variations
                                        as $variation
                                    ): ?>

                                        <tr>

                                            <td>

                                                <?= e(
                                                    $variation[
                                                        "attribute"
                                                    ]
                                                ) ?>

                                            </td>


                                            <td>

                                                <?= e(
                                                    $variation[
                                                        "value"
                                                    ]
                                                ) ?>

                                            </td>


                                            <td>

                                                <?= (int)
                                                    $variation[
                                                        "stock"
                                                    ] ?>

                                            </td>


                                            <td>

                                                <form
                                                    method="POST"
                                                    onsubmit="
                                                        return confirm(
                                                            'Are you sure you want to delete this variation?'
                                                        );
                                                    "
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="delete"
                                                    >


                                                    <input
                                                        type="hidden"
                                                        name="variation_id"
                                                        value="<?= (int)
                                                            $variation[
                                                                "id"
                                                            ] ?>"
                                                    >


                                                    <button
                                                        type="submit"
                                                        class="btn btn-danger btn-sm"
                                                    >

                                                        Delete

                                                    </button>

                                                </form>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>


                    <?php endif; ?>


                </div>

            </div>

        </div>


    </div>

</div>


<?php

require_once "../includes/footer.php";

?>