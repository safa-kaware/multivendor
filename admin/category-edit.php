<?php

require_once "../config/app.php";


requireAdmin();


/*
|--------------------------------------------------------------------------
| Get Category ID
|--------------------------------------------------------------------------
*/

$categoryId =
    (int) (
        $_GET["id"]
        ?? 0
    );


if (
    $categoryId <= 0
) {

    redirect(
        BASE_URL
        . "admin/categories.php"
    );

}


/*
|--------------------------------------------------------------------------
| Fetch Category
|--------------------------------------------------------------------------
*/

$stmt =
    $pdo->prepare(
        "SELECT *
         FROM categories
         WHERE id = ?
         LIMIT 1"
    );


$stmt->execute([
    $categoryId
]);


$category =
    $stmt->fetch();


if (
    !$category
) {

    redirect(
        BASE_URL
        . "admin/categories.php"
    );

}


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


    $status =
        $_POST["status"]
        ?? "active";


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if (
        $name === ""
    ) {

        $error =
            "Category name is required.";

    }


    /*
    |--------------------------------------------------------------------------
    | Generate Slug
    |--------------------------------------------------------------------------
    */

    if (
        $error === ""
    ) {


        if (
            $name !==
            $category["name"]
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
            | Check Existing Slug
            |--------------------------------------------------------------------------
            */

            $stmt =
                $pdo->prepare(
                    "SELECT id

                     FROM categories

                     WHERE slug = ?

                     AND id != ?

                     LIMIT 1"
                );


            $stmt->execute([

                $slug,

                $categoryId

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
                $category["slug"];

        }


        /*
        |--------------------------------------------------------------------------
        | Existing Image
        |--------------------------------------------------------------------------
        */

        $imageName =
            $category["image"];


        /*
        |--------------------------------------------------------------------------
        | New Image Upload
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


                    $extension =
                        strtolower(
                            pathinfo(
                                $_FILES["image"]["name"],
                                PATHINFO_EXTENSION
                            )
                        );


                    $newImageName =
                        uniqid(
                            "category_",
                            true
                        )
                        . "."
                        . $extension;


                    $uploadDirectory =
                        "../uploads/categories/";


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
                                $category["image"]
                            )
                        ) {


                            $oldImagePath =
                                $uploadDirectory
                                . $category["image"];


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
        | Update Category
        |--------------------------------------------------------------------------
        */

        if (
            $error === ""
        ) {


            $stmt =
                $pdo->prepare(
                    "UPDATE categories

                     SET

                        name = ?,

                        slug = ?,

                        description = ?,

                        image = ?,

                        status = ?

                     WHERE id = ?"
                );


            $stmt->execute([

                $name,

                $slug,

                $description,

                $imageName,

                $status,

                $categoryId

            ]);


            redirect(
                BASE_URL
                . "admin/categories.php"
            );

        }

    }

}


$pageTitle =
    "Edit Category | "
    . APP_NAME;


require_once "includes/header.php";

?>


<h1 class="fw-bold mb-4">

    Edit Category

</h1>


<div
    class="card border-0 shadow-sm"
>


    <div
        class="card-body p-4"
    >


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


                <!-- Name -->


                <div class="col-md-8">


                    <label
                        class="form-label"
                    >

                        Category Name

                    </label>


                    <input
                        type="text"
                        name="name"
                        class="form-control"
                        required
                        value="<?= e(
                            $_POST["name"]
                            ?? $category["name"]
                        ) ?>"
                    >


                </div>


                <!-- Status -->


                <div class="col-md-4">


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
                                    ?? $category["status"]
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
                                    ?? $category["status"]
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
                        ?? $category["description"]
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
                            $category["image"]
                        )
                    ): ?>


                        <div class="mb-3">


                            <img
                                src="<?= BASE_URL ?>uploads/categories/<?= e(
                                    $category["image"]
                                ) ?>"
                                alt="<?= e(
                                    $category["name"]
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


                <!-- Buttons -->


                <div
                    class="col-12 mt-4"
                >


                    <button
                        type="submit"
                        class="btn btn-dark"
                    >

                        <i
                            class="bi bi-check-lg"
                        ></i>

                        Update Category

                    </button>


                    <a
                        href="<?= BASE_URL ?>admin/categories.php"
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