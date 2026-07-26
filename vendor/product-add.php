<?php

require_once "../config/app.php";


/*
|--------------------------------------------------------------------------
| REQUIRE APPROVED VENDOR
|--------------------------------------------------------------------------
*/

requireVendor($pdo);


/*
|--------------------------------------------------------------------------
| GET VENDOR ID
|--------------------------------------------------------------------------
*/

$vendorId = currentVendorId($pdo);

if (!$vendorId) {

    http_response_code(403);

    die("Access denied.");

}


/*
|--------------------------------------------------------------------------
| VARIABLES
|--------------------------------------------------------------------------
*/

$errors = [];

$name = "";
$description = "";
$price = "";
$stock = "";
$categoryId = "";
$status = "active";


/*
|--------------------------------------------------------------------------
| LOAD CATEGORIES
|--------------------------------------------------------------------------
*/

try {

    $categoryStmt = $pdo->query(
        "SELECT id, name
         FROM categories
         ORDER BY name ASC"
    );

    $categories =
        $categoryStmt->fetchAll(
            PDO::FETCH_ASSOC
        );

} catch (PDOException $e) {

    $categories = [];

    $errors[] =
        "Unable to load categories.";

}


/*
|--------------------------------------------------------------------------
| HANDLE FORM SUBMISSION
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"]
    === "POST"
) {


    /*
    |--------------------------------------------------------------------------
    | GET FORM DATA
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


    $categoryId =
        trim(
            $_POST["category_id"]
            ?? ""
        );


    $status =
        $_POST["status"]
        ?? "active";


    /*
    |--------------------------------------------------------------------------
    | VALIDATE PRODUCT NAME
    |--------------------------------------------------------------------------
    */

    if (
        $name === ""
    ) {

        $errors[] =
            "Product name is required.";

    }


    if (
        strlen($name)
        >
        255
    ) {

        $errors[] =
            "Product name cannot exceed 255 characters.";

    }


    /*
    |--------------------------------------------------------------------------
    | VALIDATE PRICE
    |--------------------------------------------------------------------------
    */

    if (
        $price === ""
        ||
        !is_numeric($price)
        ||
        (float) $price < 0
    ) {

        $errors[] =
            "Please enter a valid product price.";

    }


    /*
    |--------------------------------------------------------------------------
    | VALIDATE STOCK
    |--------------------------------------------------------------------------
    */

    if (
        $stock === ""
        ||
        filter_var(
            $stock,
            FILTER_VALIDATE_INT
        ) === false
        ||
        (int) $stock < 0
    ) {

        $errors[] =
            "Please enter a valid stock quantity.";

    }


    /*
    |--------------------------------------------------------------------------
    | VALIDATE CATEGORY
    |--------------------------------------------------------------------------
    */

    if (
        $categoryId === ""
        ||
        !ctype_digit(
            $categoryId
        )
    ) {

        $errors[] =
            "Please select a valid category.";

    }


    /*
    |--------------------------------------------------------------------------
    | VALIDATE STATUS
    |--------------------------------------------------------------------------
    */

    if (
        !in_array(
            $status,
            [
                "active",
                "inactive",
                "draft"
            ],
            true
        )
    ) {

        $status =
            "active";

    }


    /*
    |--------------------------------------------------------------------------
    | IMAGE VARIABLE
    |--------------------------------------------------------------------------
    */

    $imageName = null;


    /*
    |--------------------------------------------------------------------------
    | IMAGE UPLOAD
    |--------------------------------------------------------------------------
    */

    if (
        isset(
            $_FILES["image"]
        )
        &&
        $_FILES["image"]["error"]
        !== UPLOAD_ERR_NO_FILE
    ) {


        /*
        |--------------------------------------------------------------------------
        | CHECK UPLOAD ERROR
        |--------------------------------------------------------------------------
        */

        if (
            $_FILES["image"]["error"]
            !== UPLOAD_ERR_OK
        ) {

            $errors[] =
                "There was an error uploading the image.";

        } else {


            /*
            |--------------------------------------------------------------------------
            | ALLOWED MIME TYPES
            |--------------------------------------------------------------------------
            */

            $allowedTypes = [

                "image/jpeg",
                "image/png",
                "image/webp",
                "image/gif"

            ];


            /*
            |--------------------------------------------------------------------------
            | DETECT MIME TYPE
            |--------------------------------------------------------------------------
            */

            $fileType =
                mime_content_type(
                    $_FILES["image"]["tmp_name"]
                );


            /*
            |--------------------------------------------------------------------------
            | VALIDATE MIME TYPE
            |--------------------------------------------------------------------------
            */

            if (
                !in_array(
                    $fileType,
                    $allowedTypes,
                    true
                )
            ) {

                $errors[] =
                    "Only JPG, PNG, WEBP and GIF images are allowed.";

            }


            /*
            |--------------------------------------------------------------------------
            | VALIDATE FILE SIZE
            |--------------------------------------------------------------------------
            */

            if (
                $_FILES["image"]["size"]
                >
                5 * 1024 * 1024
            ) {

                $errors[] =
                    "Image size must be less than 5 MB.";

            }


            /*
            |--------------------------------------------------------------------------
            | SAVE IMAGE
            |--------------------------------------------------------------------------
            */

            if (
                empty($errors)
            ) {


                /*
                |--------------------------------------------------------------------------
                | GET EXTENSION
                |--------------------------------------------------------------------------
                */

                $extension =
                    strtolower(
                        pathinfo(
                            $_FILES["image"]["name"],
                            PATHINFO_EXTENSION
                        )
                    );


                /*
                |--------------------------------------------------------------------------
                | GENERATE UNIQUE IMAGE NAME
                |--------------------------------------------------------------------------
                */

                $imageName =
                    uniqid(
                        "product_",
                        true
                    )
                    .
                    "."
                    .
                    $extension;


                /*
                |--------------------------------------------------------------------------
                | UPLOAD DIRECTORY
                |--------------------------------------------------------------------------
                */

                $uploadDirectory =

                    __DIR__
                    .
                    "/../uploads/products/";


                /*
                |--------------------------------------------------------------------------
                | CREATE DIRECTORY IF NEEDED
                |--------------------------------------------------------------------------
                */

                if (
                    !is_dir(
                        $uploadDirectory
                    )
                ) {

                    mkdir(
                        $uploadDirectory,
                        0755,
                        true
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | MOVE IMAGE
                |--------------------------------------------------------------------------
                */

                if (
                    !move_uploaded_file(

                        $_FILES["image"]["tmp_name"],

                        $uploadDirectory
                        .
                        $imageName

                    )
                ) {

                    $errors[] =
                        "Unable to save uploaded image.";

                    $imageName =
                        null;

                }

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | INSERT PRODUCT
    |--------------------------------------------------------------------------
    */

    if (
        empty($errors)
    ) {


        try {


            /*
            |--------------------------------------------------------------------------
            | GENERATE UNIQUE SLUG
            |--------------------------------------------------------------------------
            */

            $slugBase =

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


            $slug =

                $slugBase
                .
                "-"
                .
                bin2hex(
                    random_bytes(5)
                );


            /*
            |--------------------------------------------------------------------------
            | GENERATE UNIQUE SKU
            |--------------------------------------------------------------------------
            |
            | Example:
            |
            | TECH-COMP-7F4A21
            |
            | This prevents duplicate SKU errors.
            |
            */

            $prefix = "PROD";


            $namePrefix =

                strtoupper(

                    substr(

                        preg_replace(

                            "/[^A-Za-z0-9]/",

                            "",

                            $name

                        ),

                        0,

                        4

                    )

                );


            if (
                $namePrefix !== ""
            ) {

                $prefix =
                    $namePrefix;

            }


            $sku =

                $prefix
                .
                "-"
                .
                strtoupper(
                    bin2hex(
                        random_bytes(4)
                    )
                );


            /*
            |--------------------------------------------------------------------------
            | INSERT INTO DATABASE
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare(

                "INSERT INTO products

                (
                    vendor_id,
                    category_id,
                    name,
                    slug,
                    description,
                    price,
                    stock,
                    sku,
                    image,
                    status
                )

                VALUES

                (
                    :vendor_id,
                    :category_id,
                    :name,
                    :slug,
                    :description,
                    :price,
                    :stock,
                    :sku,
                    :image,
                    :status
                )"

            );


            $stmt->execute([

                ":vendor_id" =>
                    (int) $vendorId,

                ":category_id" =>
                    (int) $categoryId,

                ":name" =>
                    $name,

                ":slug" =>
                    $slug,

                ":description" =>
                    $description,

                ":price" =>
                    (float) $price,

                ":stock" =>
                    (int) $stock,

                ":sku" =>
                    $sku,

                ":image" =>
                    $imageName,

                ":status" =>
                    $status

            ]);


            /*
            |--------------------------------------------------------------------------
            | SUCCESS MESSAGE
            |--------------------------------------------------------------------------
            */

            $_SESSION[
                "success_message"
            ] =

                "Product added successfully.";


            /*
            |--------------------------------------------------------------------------
            | REDIRECT
            |--------------------------------------------------------------------------
            */

            redirect(

                BASE_URL
                .
                "vendor/products.php"

            );


        } catch (
            PDOException $e
        ) {


            /*
            |--------------------------------------------------------------------------
            | DELETE IMAGE IF DATABASE INSERT FAILS
            |--------------------------------------------------------------------------
            */

            if (
                $imageName !== null
            ) {


                $uploadedFile =

                    __DIR__
                    .
                    "/../uploads/products/"
                    .
                    $imageName;


                if (
                    file_exists(
                        $uploadedFile
                    )
                ) {

                    unlink(
                        $uploadedFile
                    );

                }

            }


            /*
            |--------------------------------------------------------------------------
            | USER-FRIENDLY ERROR
            |--------------------------------------------------------------------------
            */

            $errors[] =

                "Unable to add product. Please try again.";

        }

    }

}


/*
|--------------------------------------------------------------------------
| PAGE TITLE
|--------------------------------------------------------------------------
*/

$pageTitle =

    "Add Product | "
    .
    APP_NAME;


require_once "../includes/header.php";

?>


<div class="container py-5">


    <!-- HEADER -->

    <div class="mb-4">


        <a
            href="<?= BASE_URL ?>vendor/products.php"
            class="text-decoration-none text-muted"
        >

            <i class="bi bi-arrow-left"></i>

            Back to Products

        </a>


        <h1 class="fw-bold mt-3 mb-1">

            Add New Product

        </h1>


        <p class="text-muted">

            Add a product to your store and start selling.

        </p>


    </div>



    <!-- ERRORS -->

    <?php if (
        !empty($errors)
    ): ?>


        <div class="alert alert-danger">


            <strong>

                Please fix the following:

            </strong>


            <ul class="mb-0 mt-2">


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



    <!-- FORM -->

    <form
        method="POST"
        enctype="multipart/form-data"
    >


        <div class="row g-4">


            <!-- MAIN FORM -->

            <div class="col-lg-8">


                <div
                    class="card border-0 shadow-sm"
                >


                    <div
                        class="card-body p-4"
                    >


                        <h4 class="fw-bold mb-4">

                            Product Information

                        </h4>



                        <!-- PRODUCT NAME -->

                        <div class="mb-4">


                            <label
                                class="form-label fw-semibold"
                            >

                                Product Name

                            </label>


                            <input
                                type="text"
                                name="name"
                                class="form-control form-control-lg"
                                placeholder="Enter product name"
                                value="<?= e(
                                    $name
                                ) ?>"
                                required
                            >


                        </div>



                        <!-- CATEGORY -->

                        <div class="mb-4">


                            <label
                                class="form-label fw-semibold"
                            >

                                Category

                            </label>


                            <select
                                name="category_id"
                                class="form-select form-select-lg"
                                required
                            >


                                <option
                                    value=""
                                >

                                    Select Category

                                </option>


                                <?php foreach (
                                    $categories
                                    as $category
                                ): ?>


                                    <option
                                        value="<?= (int) $category["id"] ?>"
                                        <?= (string) $categoryId
                                            ===
                                            (string) $category["id"]
                                            ? "selected"
                                            : "" ?>
                                    >

                                        <?= e(
                                            $category["name"]
                                        ) ?>

                                    </option>


                                <?php endforeach; ?>


                            </select>


                        </div>



                        <!-- DESCRIPTION -->

                        <div class="mb-4">


                            <label
                                class="form-label fw-semibold"
                            >

                                Description

                            </label>


                            <textarea
                                name="description"
                                class="form-control"
                                rows="7"
                                placeholder="Describe your product..."
                            ><?= e(
                                $description
                            ) ?></textarea>


                        </div>



                        <!-- PRICE AND STOCK -->

                        <div class="row g-3">


                            <!-- PRICE -->

                            <div class="col-md-6">


                                <label
                                    class="form-label fw-semibold"
                                >

                                    Price (₹)

                                </label>


                                <div
                                    class="input-group"
                                >


                                    <span
                                        class="input-group-text"
                                    >

                                        ₹

                                    </span>


                                    <input
                                        type="number"
                                        name="price"
                                        class="form-control"
                                        min="0"
                                        step="0.01"
                                        placeholder="0.00"
                                        value="<?= e(
                                            $price
                                        ) ?>"
                                        required
                                    >


                                </div>


                            </div>



                            <!-- STOCK -->

                            <div class="col-md-6">


                                <label
                                    class="form-label fw-semibold"
                                >

                                    Stock Quantity

                                </label>


                                <input
                                    type="number"
                                    name="stock"
                                    class="form-control"
                                    min="0"
                                    step="1"
                                    placeholder="0"
                                    value="<?= e(
                                        $stock
                                    ) ?>"
                                    required
                                >


                            </div>


                        </div>


                    </div>


                </div>


            </div>



            <!-- SIDEBAR -->

            <div class="col-lg-4">


                <!-- IMAGE -->

                <div
                    class="card border-0 shadow-sm mb-4"
                >


                    <div
                        class="card-body p-4"
                    >


                        <h5 class="fw-bold mb-3">

                            Product Image

                        </h5>


                        <div
                            class="border rounded p-3 text-center mb-3"
                        >


                            <div
                                id="imagePreview"
                                class="text-muted py-5"
                            >


                                <i
                                    class="bi bi-image fs-1"
                                ></i>


                                <p class="mb-0 mt-2">

                                    Image preview

                                </p>


                            </div>


                        </div>


                        <input
                            type="file"
                            name="image"
                            id="image"
                            class="form-control"
                            accept="image/jpeg,image/png,image/webp,image/gif"
                        >


                        <small
                            class="text-muted"
                        >

                            Maximum size: 5 MB.

                        </small>


                    </div>


                </div>



                <!-- STATUS -->

                <div
                    class="card border-0 shadow-sm mb-4"
                >


                    <div
                        class="card-body p-4"
                    >


                        <h5 class="fw-bold mb-3">

                            Product Status

                        </h5>


                        <select
                            name="status"
                            class="form-select"
                        >


                            <option
                                value="active"
                                <?= $status === "active"
                                    ? "selected"
                                    : "" ?>
                            >

                                Active

                            </option>


                            <option
                                value="inactive"
                                <?= $status === "inactive"
                                    ? "selected"
                                    : "" ?>
                            >

                                Inactive

                            </option>


                            <option
                                value="draft"
                                <?= $status === "draft"
                                    ? "selected"
                                    : "" ?>
                            >

                                Draft

                            </option>


                        </select>


                        <small
                            class="text-muted d-block mt-2"
                        >

                            Active products are visible to customers.

                        </small>


                    </div>


                </div>



                <!-- ACTIONS -->

                <div
                    class="d-grid gap-2"
                >


                    <button
                        type="submit"
                        class="btn btn-dark btn-lg"
                    >

                        <i
                            class="bi bi-check-lg me-1"
                        ></i>

                        Add Product

                    </button>


                    <a
                        href="<?= BASE_URL ?>vendor/products.php"
                        class="btn btn-outline-secondary"
                    >

                        Cancel

                    </a>


                </div>


            </div>


        </div>


    </form>


</div>



<script>

document
    .getElementById("image")
    .addEventListener(
        "change",
        function(event) {

            const file =
                event.target.files[0];

            const preview =
                document.getElementById(
                    "imagePreview"
                );


            if (!file) {

                preview.innerHTML = `

                    <i class="bi bi-image fs-1"></i>

                    <p class="mb-0 mt-2">

                        Image preview

                    </p>

                `;

                return;

            }


            const reader =
                new FileReader();


            reader.onload =
                function(e) {

                    preview.innerHTML = `

                        <img
                            src="${e.target.result}"
                            class="img-fluid rounded"
                            style="
                                max-height: 220px;
                                object-fit: contain;
                            "
                        >

                    `;

                };


            reader.readAsDataURL(
                file
            );

        }
    );

</script>



<?php

require_once "../includes/footer.php";

?>