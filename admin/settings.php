<?php

require_once "../config/app.php";


requireAdmin();


/*
|--------------------------------------------------------------------------
| Define Manageable Settings
|--------------------------------------------------------------------------
*/

$settingFields = [

    "site_name" => "Site Name",
    "site_tagline" => "Site Tagline",
    "site_email" => "Contact Email",
    "site_phone" => "Contact Phone",
    "site_address" => "Business Address",

    "facebook_url" => "Facebook URL",
    "instagram_url" => "Instagram URL",
    "twitter_url" => "Twitter / X URL",

    "meta_title" => "Default Meta Title",
    "meta_description" => "Default Meta Description",
    "meta_keywords" => "Default Meta Keywords",

    "theme_primary_color" => "Theme Primary Color (hex)",

];


$success = "";

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


    $upsert =
        $pdo->prepare(
            "INSERT INTO settings
                (setting_key, setting_value)
             VALUES
                (?, ?)
             ON DUPLICATE KEY UPDATE
                setting_value = VALUES(setting_value)"
        );


    foreach (
        $settingFields
        as $key
        => $label
    ) {

        $value =
            trim(
                $_POST[$key]
                ?? ""
            );

        $upsert->execute([
            $key,
            $value
        ]);

    }


    $success =
        "Settings saved successfully.";

}


/*
|--------------------------------------------------------------------------
| Fetch Current Settings
|--------------------------------------------------------------------------
*/

$stmt =
    $pdo->query(
        "SELECT setting_key, setting_value
         FROM settings"
    );

$currentSettings = [];

foreach (
    $stmt->fetchAll(PDO::FETCH_ASSOC)
    as $row
) {

    $currentSettings[$row["setting_key"]] =
        $row["setting_value"];

}


$pageTitle =
    "Site Settings | "
    . APP_NAME;


require_once "includes/header.php";

?>


<div class="mb-4">

    <h1 class="fw-bold">
        Site Settings
    </h1>

    <p class="text-muted mb-0">
        Manage general site info, social links, and SEO defaults.
    </p>

</div>


<?php if ($success !== ""): ?>

    <div class="alert alert-success">
        <?= e($success) ?>
    </div>

<?php endif; ?>


<?php if ($error !== ""): ?>

    <div class="alert alert-danger">
        <?= e($error) ?>
    </div>

<?php endif; ?>


<form method="POST">

    <div class="card border-0 shadow-sm mb-4">

        <div class="card-body">

            <h5 class="fw-bold mb-3">
                General
            </h5>

            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label">Site Name</label>
                    <input
                        type="text"
                        name="site_name"
                        class="form-control"
                        value="<?= e($currentSettings["site_name"] ?? "") ?>"
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label">Site Tagline</label>
                    <input
                        type="text"
                        name="site_tagline"
                        class="form-control"
                        value="<?= e($currentSettings["site_tagline"] ?? "") ?>"
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label">Contact Email</label>
                    <input
                        type="email"
                        name="site_email"
                        class="form-control"
                        value="<?= e($currentSettings["site_email"] ?? "") ?>"
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label">Contact Phone</label>
                    <input
                        type="text"
                        name="site_phone"
                        class="form-control"
                        value="<?= e($currentSettings["site_phone"] ?? "") ?>"
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label">Theme Primary Color</label>
                    <input
                        type="text"
                        name="theme_primary_color"
                        class="form-control"
                        placeholder="#1a1a1a"
                        value="<?= e($currentSettings["theme_primary_color"] ?? "") ?>"
                    >
                </div>

                <div class="col-12">
                    <label class="form-label">Business Address</label>
                    <textarea
                        name="site_address"
                        class="form-control"
                        rows="2"
                    ><?= e($currentSettings["site_address"] ?? "") ?></textarea>
                </div>

            </div>

        </div>

    </div>


    <div class="card border-0 shadow-sm mb-4">

        <div class="card-body">

            <h5 class="fw-bold mb-3">
                Social Links
            </h5>

            <div class="row g-3">

                <div class="col-md-4">
                    <label class="form-label">Facebook URL</label>
                    <input
                        type="url"
                        name="facebook_url"
                        class="form-control"
                        value="<?= e($currentSettings["facebook_url"] ?? "") ?>"
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label">Instagram URL</label>
                    <input
                        type="url"
                        name="instagram_url"
                        class="form-control"
                        value="<?= e($currentSettings["instagram_url"] ?? "") ?>"
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label">Twitter / X URL</label>
                    <input
                        type="url"
                        name="twitter_url"
                        class="form-control"
                        value="<?= e($currentSettings["twitter_url"] ?? "") ?>"
                    >
                </div>

            </div>

        </div>

    </div>


    <div class="card border-0 shadow-sm mb-4">

        <div class="card-body">

            <h5 class="fw-bold mb-3">
                SEO Defaults
            </h5>

            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label">Default Meta Title</label>
                    <input
                        type="text"
                        name="meta_title"
                        class="form-control"
                        value="<?= e($currentSettings["meta_title"] ?? "") ?>"
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label">Default Meta Keywords</label>
                    <input
                        type="text"
                        name="meta_keywords"
                        class="form-control"
                        placeholder="comma, separated, keywords"
                        value="<?= e($currentSettings["meta_keywords"] ?? "") ?>"
                    >
                </div>

                <div class="col-12">
                    <label class="form-label">Default Meta Description</label>
                    <textarea
                        name="meta_description"
                        class="form-control"
                        rows="2"
                    ><?= e($currentSettings["meta_description"] ?? "") ?></textarea>
                </div>

            </div>

        </div>

    </div>


    <button
        type="submit"
        class="btn btn-dark"
    >
        Save Settings
    </button>

</form>


<?php

require_once "includes/footer.php";