<?php

require_once "../config/app.php";


requireAdmin();


/*
|--------------------------------------------------------------------------
| Handle Delete
|--------------------------------------------------------------------------
*/

if (
    isset($_GET["delete"])
) {

    $deleteId =
        (int) $_GET["delete"];

    $stmt =
        $pdo->prepare(
            "SELECT image
             FROM banners
             WHERE id = ?"
        );

    $stmt->execute([$deleteId]);

    $bannerImage =
        $stmt->fetchColumn();

    if ($bannerImage) {

        $imagePath =
            "../uploads/banners/" . $bannerImage;

        if (is_file($imagePath)) {

            unlink($imagePath);

        }

    }

    $delete =
        $pdo->prepare(
            "DELETE FROM banners
             WHERE id = ?"
        );

    $delete->execute([$deleteId]);

    redirect(
        BASE_URL
        . "admin/banners.php"
    );

}


/*
|--------------------------------------------------------------------------
| Handle Toggle Status
|--------------------------------------------------------------------------
*/

if (
    isset($_GET["toggle"])
) {

    $toggleId =
        (int) $_GET["toggle"];

    $stmt =
        $pdo->prepare(
            "SELECT status
             FROM banners
             WHERE id = ?"
        );

    $stmt->execute([$toggleId]);

    $current =
        $stmt->fetchColumn();

    if ($current !== false) {

        $newStatus =
            $current === "active"
                ? "inactive"
                : "active";

        $update =
            $pdo->prepare(
                "UPDATE banners
                 SET status = ?
                 WHERE id = ?"
            );

        $update->execute([
            $newStatus,
            $toggleId
        ]);

    }

    redirect(
        BASE_URL
        . "admin/banners.php"
    );

}


$error = "";


/*
|--------------------------------------------------------------------------
| Handle Add Banner
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"]
    === "POST"
) {

    $title =
        trim(
            $_POST["title"]
            ?? ""
        );

    $subtitle =
        trim(
            $_POST["subtitle"]
            ?? ""
        );

    $buttonText =
        trim(
            $_POST["button_text"]
            ?? ""
        );

    $buttonLink =
        trim(
            $_POST["button_link"]
            ?? ""
        );

    $sortOrder =
        (int) (
            $_POST["sort_order"]
            ?? 0
        );

    $status =
        $_POST["status"]
        ?? "active";

    $imageName = null;


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($title === "") {

        $error =
            "Banner title is required.";

    }


    /*
    |--------------------------------------------------------------------------
    | Handle Image Upload
    |--------------------------------------------------------------------------
    */

    if (
        $error === ""
        && (
            !isset($_FILES["image"])
            || $_FILES["image"]["error"] === UPLOAD_ERR_NO_FILE
        )
    ) {

        $error =
            "Banner image is required.";

    }

    if (
        $error === ""
        && $_FILES["image"]["error"] !== UPLOAD_ERR_OK
    ) {

        $error =
            "Image upload failed.";

    }

    if ($error === "") {

        $allowedTypes = [
            "image/jpeg",
            "image/png",
            "image/webp",
        ];

        $fileType =
            mime_content_type(
                $_FILES["image"]["tmp_name"]
            );

        if (
            !in_array($fileType, $allowedTypes, true)
        ) {

            $error =
                "Only JPG, PNG, and WEBP images are allowed.";

        } elseif (
            $_FILES["image"]["size"] > 5 * 1024 * 1024
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
                uniqid("banner_", true)
                . "."
                . $extension;

            $uploadDirectory =
                "../uploads/banners/";

            if (!is_dir($uploadDirectory)) {

                mkdir($uploadDirectory, 0755, true);

            }

            move_uploaded_file(
                $_FILES["image"]["tmp_name"],
                $uploadDirectory . $imageName
            );

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Insert
    |--------------------------------------------------------------------------
    */

    if ($error === "") {

        $stmt =
            $pdo->prepare(
                "INSERT INTO banners

                (
                    title,
                    subtitle,
                    image,
                    button_text,
                    button_link,
                    status,
                    sort_order
                )

                VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

        $stmt->execute([
            $title,
            $subtitle !== "" ? $subtitle : null,
            $imageName,
            $buttonText !== "" ? $buttonText : null,
            $buttonLink !== "" ? $buttonLink : null,
            $status,
            $sortOrder,
        ]);

        redirect(
            BASE_URL
            . "admin/banners.php"
        );

    }

}


/*
|--------------------------------------------------------------------------
| Fetch Banners
|--------------------------------------------------------------------------
*/

$stmt =
    $pdo->query(
        "SELECT *
         FROM banners
         ORDER BY sort_order ASC, created_at DESC"
    );

$banners =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


$pageTitle =
    "Manage Banners | "
    . APP_NAME;


require_once "includes/header.php";

?>


<div class="mb-4">

    <h1 class="fw-bold">
        Homepage Banners
    </h1>

    <p class="text-muted mb-0">
        Manage the rotating banners shown on your homepage hero section.
    </p>

</div>


<?php if ($error !== ""): ?>

    <div class="alert alert-danger">
        <?= e($error) ?>
    </div>

<?php endif; ?>


<div class="row g-4">

    <div class="col-lg-5">

        <div class="card border-0 shadow-sm">

            <div class="card-body">

                <h5 class="fw-bold mb-3">
                    Add Banner
                </h5>

                <form
                    method="POST"
                    enctype="multipart/form-data"
                >

                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input
                            type="text"
                            name="title"
                            class="form-control"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Subtitle</label>
                        <input
                            type="text"
                            name="subtitle"
                            class="form-control"
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Banner Image</label>
                        <input
                            type="file"
                            name="image"
                            class="form-control"
                            accept="image/jpeg,image/png,image/webp"
                            required
                        >
                        <small class="text-muted">
                            JPG, PNG, or WEBP. Max 5MB. Recommended: wide banner image (e.g. 1600x500).
                        </small>
                    </div>

                    <div class="row g-3">

                        <div class="col-6">
                            <label class="form-label">Button Text</label>
                            <input
                                type="text"
                                name="button_text"
                                class="form-control"
                                placeholder="Shop Now"
                            >
                        </div>

                        <div class="col-6">
                            <label class="form-label">Button Link</label>
                            <input
                                type="text"
                                name="button_link"
                                class="form-control"
                                placeholder="search.php"
                            >
                        </div>

                        <div class="col-6">
                            <label class="form-label">Sort Order</label>
                            <input
                                type="number"
                                name="sort_order"
                                class="form-control"
                                value="0"
                            >
                        </div>

                        <div class="col-6">
                            <label class="form-label">Status</label>
                            <select
                                name="status"
                                class="form-select"
                            >
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                    </div>

                    <button
                        type="submit"
                        class="btn btn-dark mt-4"
                    >
                        Add Banner
                    </button>

                </form>

            </div>

        </div>

    </div>


    <div class="col-lg-7">

        <?php if (empty($banners)): ?>

            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5 text-muted">
                    No banners yet. Add one to get started.
                </div>
            </div>

        <?php else: ?>

            <?php foreach ($banners as $banner): ?>

                <div class="card border-0 shadow-sm mb-3">

                    <div class="row g-0">

                        <div class="col-4">

                            <img
                                src="<?= BASE_URL ?>uploads/banners/<?= e($banner["image"]) ?>"
                                class="w-100 h-100"
                                style="object-fit: cover; min-height: 100px;"
                                alt="<?= e($banner["title"]) ?>"
                            >

                        </div>

                        <div class="col-8">

                            <div class="card-body">

                                <div class="d-flex justify-content-between align-items-start">

                                    <div>

                                        <h6 class="fw-bold mb-1">
                                            <?= e($banner["title"]) ?>
                                        </h6>

                                        <?php if (!empty($banner["subtitle"])): ?>
                                            <p class="text-muted small mb-1">
                                                <?= e($banner["subtitle"]) ?>
                                            </p>
                                        <?php endif; ?>

                                        <p class="text-muted small mb-0">
                                            Sort: <?= e($banner["sort_order"]) ?>
                                        </p>

                                    </div>

                                    <a
                                        href="<?= BASE_URL ?>admin/banners.php?toggle=<?= (int) $banner["id"] ?>"
                                        class="badge <?= $banner["status"] === "active" ? "bg-success" : "bg-secondary" ?> text-decoration-none"
                                    >
                                        <?= e(ucfirst($banner["status"])) ?>
                                    </a>

                                </div>

                                <a
                                    href="<?= BASE_URL ?>admin/banners.php?delete=<?= (int) $banner["id"] ?>"
                                    class="btn btn-sm btn-outline-danger mt-2"
                                    onclick="return confirm('Delete this banner?');"
                                >
                                    <i class="bi bi-trash"></i>
                                    Delete
                                </a>

                            </div>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</div>


<?php

require_once "includes/footer.php";