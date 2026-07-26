<?php

require_once "../config/app.php";


/*
|--------------------------------------------------------------------------
| REQUIRE APPROVED VENDOR
|--------------------------------------------------------------------------
*/

requireVendor($pdo);


$vendorId =
    currentVendorId($pdo);


if (!$vendorId) {

    http_response_code(403);

    die(
        "Access denied."
    );

}


/*
|--------------------------------------------------------------------------
| GET PRODUCT ID
|--------------------------------------------------------------------------
*/

$productId =

    isset(
        $_GET["id"]
    )

    ?

    (int)
    $_GET["id"]

    :

    0;


if (
    $productId <= 0
) {

    die(
        "Invalid product ID."
    );

}


/*
|--------------------------------------------------------------------------
| FETCH PRODUCT
|--------------------------------------------------------------------------
*/

$stmt =
    $pdo->prepare(

        "SELECT *

         FROM products

         WHERE id = ?

         AND vendor_id = ?

         LIMIT 1"

    );


$stmt->execute([

    $productId,

    $vendorId

]);


$product =
    $stmt->fetch();


if (
    !$product
) {

    http_response_code(404);

    die(
        "Product not found."
    );

}


/*
|--------------------------------------------------------------------------
| VARIABLES
|--------------------------------------------------------------------------
*/

$errors = [];

$name =
    $product[
        "name"
    ];

$description =
    $product[
        "description"
    ];

$price =
    $product[
        "price"
    ];

$stock =
    $product[
        "stock"
    ];

$status =
    $product[
        "status"
    ];


/*
|--------------------------------------------------------------------------
| HANDLE FORM
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


    $status =
        $_POST["status"]
        ?? "active";


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
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

        $errors[] =
            "Invalid product status.";

    }


    /*
    |--------------------------------------------------------------------------
    | IMAGE
    |--------------------------------------------------------------------------
    */

    $newImageName = null;


    if (
        isset(
            $_FILES["image"]
        )
        &&
        $_FILES[
            "image"
        ][
            "error"
        ]
        !== UPLOAD_ERR_NO_FILE
    ) {


        if (
            $_FILES[
                "image"
            ][
                "error"
            ]
            !== UPLOAD_ERR_OK
        ) {

            $errors[] =
                "There was an error uploading the image.";

        } else {


            $allowedTypes = [

                "image/jpeg",

                "image/png",

                "image/webp",

                "image/gif"

            ];


            $fileType =
                mime_content_type(
                    $_FILES[
                        "image"
                    ][
                        "tmp_name"
                    ]
                );


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


            if (
                $_FILES[
                    "image"
                ][
                    "size"
                ]
                >
                5 * 1024 * 1024
            ) {

                $errors[] =
                    "Image size must be less than 5 MB.";

            }


            if (
                empty($errors)
            ) {


                $extension =

                    strtolower(

                        pathinfo(

                            $_FILES[
                                "image"
                            ][
                                "name"
                            ],

                            PATHINFO_EXTENSION

                        )

                    );


                $newImageName =

                    uniqid(
                        "product_",
                        true
                    )

                    .

                    "."

                    .

                    $extension;


                $uploadDirectory =

                    __DIR__

                    .

                    "/../uploads/products/";


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


                if (
                    !move_uploaded_file(

                        $_FILES[
                            "image"
                        ][
                            "tmp_name"
                        ],

                        $uploadDirectory
                        .
                        $newImageName

                    )
                ) {

                    $errors[] =
                        "Unable to save uploaded image.";

                    $newImageName =
                        null;

                }

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE PRODUCT
    |--------------------------------------------------------------------------
    */

    if (
        empty($errors)
    ) {


        try {


            if (
                $newImageName
                !== null
            ) {


                $stmt =
                    $pdo->prepare(

                        "UPDATE products

                         SET

                            name = ?,

                            description = ?,

                            price = ?,

                            stock = ?,

                            image = ?,

                            status = ?

                         WHERE id = ?

                         AND vendor_id = ?"

                    );


                $stmt->execute([

                    $name,

                    $description,

                    (float)
                    $price,

                    (int)
                    $stock,

                    $newImageName,

                    $status,

                    $productId,

                    $vendorId

                ]);


                /*
                |--------------------------------------------------------------------------
                | DELETE OLD IMAGE
                |--------------------------------------------------------------------------
                */

                if (
                    !empty(
                        $product[
                            "image"
                        ]
                    )
                ) {


                    $oldImage =

                        __DIR__

                        .

                        "/../uploads/products/"

                        .

                        $product[
                            "image"
                        ];


                    if (
                        file_exists(
                            $oldImage
                        )
                    ) {

                        unlink(
                            $oldImage
                        );

                    }

                }


            } else {


                $stmt =
                    $pdo->prepare(

                        "UPDATE products

                         SET

                            name = ?,

                            description = ?,

                            price = ?,

                            stock = ?,

                            status = ?

                         WHERE id = ?

                         AND vendor_id = ?"

                    );


                $stmt->execute([

                    $name,

                    $description,

                    (float)
                    $price,

                    (int)
                    $stock,

                    $status,

                    $productId,

                    $vendorId

                ]);

            }


            $_SESSION[
                "success_message"
            ] =

                "Product updated successfully.";


            redirect(

                BASE_URL

                .

                "vendor/products.php"

            );


        } catch (
            PDOException $e
        ) {


            if (
                $newImageName
                !== null
            ) {


                $newImagePath =

                    __DIR__

                    .

                    "/../uploads/products/"

                    .

                    $newImageName;


                if (
                    file_exists(
                        $newImagePath
                    )
                ) {

                    unlink(
                        $newImagePath
                    );

                }

            }


            $errors[] =
                "Unable to update product.";

        }

    }

}


/*
|--------------------------------------------------------------------------
| PAGE TITLE
|--------------------------------------------------------------------------
*/

$pageTitle =

    "Edit Product | "

    . APP_NAME;


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

            Edit Product

        </h1>


        <p class="text-muted">

            Update your product information and inventory.

        </p>


    </div>



    <!-- ERRORS -->

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



    <form
        method="POST"
        enctype="multipart/form-data"
    >


        <div class="row g-4">


            <!-- MAIN -->

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
                                value="<?= e(
                                    $name
                                ) ?>"
                                required
                            >


                        </div>



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
                            ><?= e(
                                $description
                            ) ?></textarea>


                        </div>



                        <div class="row g-3">


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
                                        value="<?= e(
                                            $price
                                        ) ?>"
                                        required
                                    >

                                </div>


                            </div>



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
                            id="imagePreview"
                            class="border rounded p-3 text-center mb-3"
                        >


                            <?php if (
                                !empty(
                                    $product[
                                        "image"
                                    ]
                                )
                            ): ?>


                                <img
                                    src="<?= BASE_URL ?>uploads/products/<?= e(
                                        $product[
                                            "image"
                                        ]
                                    ) ?>"
                                    class="img-fluid rounded"
                                    style="
                                        max-height: 220px;
                                        object-fit: contain;
                                    "
                                >


                            <?php else: ?>


                                <div
                                    class="py-5 text-muted"
                                >

                                    <i
                                        class="bi bi-image fs-1"
                                    ></i>


                                    <p class="mb-0">

                                        No image uploaded

                                    </p>

                                </div>


                            <?php endif; ?>


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

                            Upload a new image to replace the current one.

                        </small>


                    </div>


                </div>



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


                    </div>


                </div>



                <div class="d-grid gap-2">


                    <button
                        type="submit"
                        class="btn btn-dark btn-lg"
                    >

                        <i
                            class="bi bi-check-lg me-1"
                        ></i>

                        Save Changes

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