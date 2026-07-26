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

        $stmt =
            $pdo->prepare(
                "SELECT id

                 FROM categories

                 WHERE slug = ?

                 LIMIT 1"
            );


        $stmt->execute([
            $slug
        ]);


        if (
            $stmt->fetch()
        ) {

            $slug .=
                "-"
                . time();

        }


        /*
        |--------------------------------------------------------------------------
        | Image Upload
        |--------------------------------------------------------------------------
        */

        $imageName =
            null;


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


                    $imageName =
                        uniqid(
                            "category_",
                            true
                        )
                        . "."
                        . $extension;


                    $uploadDirectory =
                        "../uploads/categories/";


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


                    $uploadPath =
                        $uploadDirectory
                        . $imageName;


                    if (
                        !move_uploaded_file(
                            $_FILES["image"]["tmp_name"],
                            $uploadPath
                        )
                    ) {

                        $error =
                            "Unable to save uploaded image.";

                    }

                }

            }

        }


        /*
        |--------------------------------------------------------------------------
        | Insert Category
        |--------------------------------------------------------------------------
        */

        if (
            $error === ""
        ) {


            $stmt =
                $pdo->prepare(
                    "INSERT INTO categories

                    (
                        name,
                        slug,
                        description,
                        image,
                        status
                    )

                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?
                    )"
                );


            $stmt->execute([

                $name,

                $slug,

                $description,

                $imageName,

                $status

            ]);


            redirect(
                BASE_URL
                . "admin/categories.php"
            );

        }

    }

}


$pageTitle =
    "Add Category | "
    . APP_NAME;


require_once "includes/header.php";

?>


<h1 class="fw-bold mb-4">

    Add Category

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
                            ?? ""
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


                        <option value="active">

                            Active

                        </option>


                        <option value="inactive">

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
                        ?? ""
                    ) ?></textarea>


                </div>


                <!-- Image -->


                <div class="col-md-6">


                    <label
                        class="form-label"
                    >

                        Category Image

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

                        JPG, PNG, or WEBP. Maximum 5MB.

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

                        Add Category

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