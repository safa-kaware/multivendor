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
| Get Product ID
|--------------------------------------------------------------------------
*/

$productId = (int) (
    $_GET["id"] ?? 0
);


if ($productId <= 0) {

    redirect(
        BASE_URL . "admin/products.php"
    );

}


/*
|--------------------------------------------------------------------------
| Fetch Product
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT *
     FROM products
     WHERE id = ?
     LIMIT 1"
);

$stmt->execute([
    $productId
]);

$product = $stmt->fetch();


if (!$product) {

    redirect(
        BASE_URL . "admin/products.php"
    );

}


$error = "";


/*
|--------------------------------------------------------------------------
| Fetch Categories
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query(
    "SELECT
        id,
        name

     FROM categories

     WHERE status = 'active'

     ORDER BY name ASC"
);

$categories = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Fetch Approved Vendors
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query(
    "SELECT
        id,
        store_name

     FROM vendors

     WHERE status = 'approved'

     ORDER BY store_name ASC"
);

$vendors = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Handle Form Submission
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"]
    === "POST"
) {


    /*
    |--------------------------------------------------------------------------
    | Get Form Data
    |--------------------------------------------------------------------------
    */

    $name =
        trim(
            $_POST["name"]
            ?? ""
        );


    $description =
        trim(
            $_POST["description"]
            ?? ""
        );


    $price =
        trim(
            $_POST["price"]
            ?? ""
        );


    $stock =
        trim(
            $_POST["stock"]
            ?? ""
        );


    $sku =
        trim(
            $_POST["sku"]
            ?? ""
        );


    $categoryId =
        (int) (
            $_POST["category_id"]
            ?? 0
        );


    $vendorId =
        (int) (
            $_POST["vendor_id"]
            ?? 0
        );


    $status =
        $_POST["status"]
        ?? "active";


    $featured =
        isset(
            $_POST["featured"]
        )
        ? 1
        : 0;


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if (
        $name === ""
    ) {

        $error =
            "Product name is required.";

    } elseif (
        $categoryId <= 0
    ) {

        $error =
            "Please select a category.";

    } elseif (
        $vendorId <= 0
    ) {

        $error =
            "Please select a vendor.";

    } elseif (
        !is_numeric($price)
        || $price < 0
    ) {

        $error =
            "Please enter a valid price.";

    } elseif (
        !filter_var(
            $stock,
            FILTER_VALIDATE_INT
        )
        && $stock !== "0"
    ) {

        $error =
            "Please enter a valid stock quantity.";

    } elseif (
        (int) $stock < 0
    ) {

        $error =
            "Stock cannot be negative.";

    }


    /*
    |--------------------------------------------------------------------------
    | Check SKU
    |--------------------------------------------------------------------------
    */

    if (
        $error === ""
        && $sku !== ""
    ) {


        $stmt = $pdo->prepare(
            "SELECT id

             FROM products

             WHERE sku = ?

             AND id != ?

             LIMIT 1"
        );


        $stmt->execute([

            $sku,

            $productId

        ]);


        if (
            $stmt->fetch()
        ) {

            $error =
                "This SKU is already used by another product.";

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Generate New Slug
    |--------------------------------------------------------------------------
    */

    if (
        $error === ""
        && $name !== $product["name"]
    ) {


        $slug =
            strtolower(
                trim(
                    preg_replace(
                        "/[^A-Za-z0-9-]+/",
                        "-",
                        $name
                    ),
                    "-"
                )
            );


        /*
        |--------------------------------------------------------------------------
        | Check Slug
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare(
            "SELECT id

             FROM products

             WHERE slug = ?

             AND id != ?

             LIMIT 1"
        );


        $stmt->execute([

            $slug,

            $productId

        ]);


        if (
            $stmt->fetch()
        ) {

            $slug .=
                "-"
                . time();

        }

    } else {

        $slug =
            $product["slug"];

    }


    /*
    |--------------------------------------------------------------------------
    | Image Handling
    |--------------------------------------------------------------------------
    */

    $imageName =
        $product["image"];


    if (
        isset(
            $_FILES["image"]
        )
        &&
        $_FILES["image"]["error"]
        !== UPLOAD_ERR_NO_FILE
    ) {


        if (
            $_FILES["image"]["error"]
            !== UPLOAD_ERR_OK
        ) {

            $error =
                "Image upload failed.";

        } else {


            $allowedTypes = [

                "image/jpeg",

                "image/png",

                "image/webp"

            ];


            $fileType =
                mime_content_type(
                    $_FILES["image"]["tmp_name"]
                );


            if (
                !in_array(
                    $fileType,
                    $allowedTypes,
                    true
                )
            ) {

                $error =
                    "Only JPG, PNG, and WEBP images are allowed.";

            } elseif (
                $_FILES["image"]["size"]
                > 5 * 1024 * 1024
            ) {

                $error =
                    "Image size must be less than 5MB.";

            } else {


                /*
                |--------------------------------------------------------------------------
                | Generate New Image Name
                |--------------------------------------------------------------------------
                */

                $extension =
                    strtolower(
                        pathinfo(
                            $_FILES["image"]["name"],
                            PATHINFO_EXTENSION
                        )
                    );


                $newImageName =
                    uniqid(
                        "product_",
                        true
                    )
                    . "."
                    . $extension;


                $uploadDirectory =
                    "../uploads/products/";


                $uploadPath =
                    $uploadDirectory
                    . $newImageName;


                if (
                    move_uploaded_file(
                        $_FILES["image"]["tmp_name"],
                        $uploadPath
                    )
                ) {


                    /*
                    |--------------------------------------------------------------------------
                    | Delete Old Image
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !empty(
                            $product["image"]
                        )
                    ) {


                        $oldImagePath =
                            $uploadDirectory
                            . $product["image"];


                        if (
                            file_exists(
                                $oldImagePath
                            )
                        ) {

                            unlink(
                                $oldImagePath
                            );

                        }

                    }


                    $imageName =
                        $newImageName;


                } else {

                    $error =
                        "Unable to save uploaded image.";

                }

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Update Product
    |--------------------------------------------------------------------------
    */

    if (
        $error === ""
    ) {


        $stmt = $pdo->prepare(
            "UPDATE products

             SET

                vendor_id = ?,

                category_id = ?,

                name = ?,

                slug = ?,

                description = ?,

                price = ?,

                stock = ?,

                sku = ?,

                image = ?,

                status = ?,

                featured = ?

             WHERE id = ?"
        );


        $stmt->execute([

            $vendorId,

            $categoryId,

            $name,

            $slug,

            $description,

            $price,

            (int) $stock,

            $sku !== ""
                ? $sku
                : null,

            $imageName,

            $status,

            $featured,

            $productId

        ]);


        redirect(
            BASE_URL
            . "admin/products.php"
        );

    }

}


$pageTitle =
    "Edit Product | "
    . APP_NAME;


require_once "includes/header.php";

?>


<h1 class="fw-bold mb-4">

    Edit Product

</h1>


<div class="card border-0 shadow-sm">


    <div class="card-body p-4">


        <?php if (
            $error
        ): ?>


            <div
                class="alert alert-danger"
            >

                <?= e(
                    $error
                ) ?>

            </div>


        <?php endif; ?>


        <form
            method="POST"
            enctype="multipart/form-data"
        >


            <div class="row g-3">


                <!-- Product Name -->


                <div class="col-md-8">


                    <label
                        class="form-label"
                    >

                        Product Name

                    </label>


                    <input
                        type="text"
                        name="name"
                        class="form-control"
                        required
                        value="<?= e(
                            $_POST["name"]
                            ?? $product["name"]
                        ) ?>"
                    >


                </div>


                <!-- SKU -->


                <div class="col-md-4">


                    <label
                        class="form-label"
                    >

                        SKU

                    </label>


                    <input
                        type="text"
                        name="sku"
                        class="form-control"
                        value="<?= e(
                            $_POST["sku"]
                            ?? $product["sku"]
                        ) ?>"
                    >


                </div>


                <!-- Category -->


                <div class="col-md-6">


                    <label
                        class="form-label"
                    >

                        Category

                    </label>


                    <select
                        name="category_id"
                        class="form-select"
                        required
                    >


                        <option value="">

                            Select Category

                        </option>


                        <?php foreach (
                            $categories
                            as $category
                        ): ?>


                            <option
                                value="<?= e(
                                    $category["id"]
                                ) ?>"
                                <?= (
                                    (
                                        $_POST["category_id"]
                                        ?? $product["category_id"]
                                    )
                                    == $category["id"]
                                )
                                    ? "selected"
                                    : ""
                                ?>
                            >

                                <?= e(
                                    $category["name"]
                                ) ?>

                            </option>


                        <?php endforeach; ?>


                    </select>


                </div>


                <!-- Vendor -->


                <div class="col-md-6">


                    <label
                        class="form-label"
                    >

                        Vendor

                    </label>


                    <select
                        name="vendor_id"
                        class="form-select"
                        required
                    >


                        <option value="">

                            Select Vendor

                        </option>


                        <?php foreach (
                            $vendors
                            as $vendor
                        ): ?>


                            <option
                                value="<?= e(
                                    $vendor["id"]
                                ) ?>"
                                <?= (
                                    (
                                        $_POST["vendor_id"]
                                        ?? $product["vendor_id"]
                                    )
                                    == $vendor["id"]
                                )
                                    ? "selected"
                                    : ""
                                ?>
                            >

                                <?= e(
                                    $vendor["store_name"]
                                ) ?>

                            </option>


                        <?php endforeach; ?>


                    </select>


                </div>


                <!-- Price -->


                <div class="col-md-6">


                    <label
                        class="form-label"
                    >

                        Price (₹)

                    </label>


                    <input
                        type="number"
                        name="price"
                        class="form-control"
                        min="0"
                        step="0.01"
                        required
                        value="<?= e(
                            $_POST["price"]
                            ?? $product["price"]
                        ) ?>"
                    >


                </div>


                <!-- Stock -->


                <div class="col-md-6">


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
                        required
                        value="<?= e(
                            $_POST["stock"]
                            ?? $product["stock"]
                        ) ?>"
                    >


                </div>


                <!-- Description -->


                <div class="col-12">


                    <label
                        class="form-label"
                    >

                        Description

                    </label>


                    <textarea
                        name="description"
                        class="form-control"
                        rows="5"
                    ><?= e(
                        $_POST["description"]
                        ?? $product["description"]
                    ) ?></textarea>


                </div>


                <!-- Current Image -->


                <div class="col-md-6">


                    <label
                        class="form-label"
                    >

                        Current Image

                    </label>


                    <?php if (
                        !empty(
                            $product["image"]
                        )
                    ): ?>


                        <div class="mb-2">


                            <img
                                src="<?= BASE_URL ?>uploads/products/<?= e(
                                    $product["image"]
                                ) ?>"
                                alt="<?= e(
                                    $product["name"]
                                ) ?>"
                                style="
                                    width: 120px;
                                    height: 120px;
                                    object-fit: cover;
                                "
                                class="rounded border"
                            >


                        </div>


                    <?php else: ?>


                        <p class="text-muted">

                            No image uploaded.

                        </p>


                    <?php endif; ?>


                    <label
                        class="form-label"
                    >

                        Upload New Image

                    </label>


                    <input
                        type="file"
                        name="image"
                        class="form-control"
                        accept=".jpg,.jpeg,.png,.webp"
                    >


                    <small
                        class="text-muted"
                    >

                        Leave empty to keep the current image.

                    </small>


                </div>


                <!-- Status -->


                <div class="col-md-3">


                    <label
                        class="form-label"
                    >

                        Status

                    </label>


                    <select
                        name="status"
                        class="form-select"
                    >


                        <option
                            value="active"
                            <?= (
                                (
                                    $_POST["status"]
                                    ?? $product["status"]
                                )
                                === "active"
                            )
                                ? "selected"
                                : ""
                            ?>
                        >

                            Active

                        </option>


                        <option
                            value="inactive"
                            <?= (
                                (
                                    $_POST["status"]
                                    ?? $product["status"]
                                )
                                === "inactive"
                            )
                                ? "selected"
                                : ""
                            ?>
                        >

                            Inactive

                        </option>


                    </select>


                </div>


                <!-- Featured -->


                <div class="col-md-3">


                    <label
                        class="form-label"
                    >

                        Featured

                    </label>


                    <div
                        class="form-check mt-2"
                    >


                        <input
                            type="checkbox"
                            name="featured"
                            class="form-check-input"
                            id="featured"
                            value="1"
                            <?= (
                                isset(
                                    $_POST["featured"]
                                )
                                    ? true
                                    : $product["featured"]
                            )
                                ? "checked"
                                : ""
                            ?>
                        >


                        <label
                            for="featured"
                            class="form-check-label"
                        >

                            Featured Product

                        </label>


                    </div>


                </div>


                <!-- Buttons -->


                <div class="col-12 mt-4">


                    <button
                        type="submit"
                        class="btn btn-dark"
                    >

                        <i class="bi bi-check-lg"></i>

                        Update Product

                    </button>


                    <a
                        href="<?= BASE_URL ?>admin/products.php"
                        class="btn btn-outline-secondary"
                    >

                        Cancel

                    </a>


                </div>


            </div>


        </form>


    </div>


</div>


<?php

require_once "includes/footer.php";

?>