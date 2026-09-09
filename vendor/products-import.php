<?php

require_once "../config/app.php";


/*
|--------------------------------------------------------------------------
| REQUIRE APPROVED VENDOR
|--------------------------------------------------------------------------
*/

requireVendor($pdo);

$vendorId = currentVendorId($pdo);

if (!$vendorId) {

    http_response_code(403);

    die("Access denied.");

}


$error = "";

$results = [];

$importedCount = 0;

$failedCount = 0;


/*
|--------------------------------------------------------------------------
| HANDLE CSV UPLOAD
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"]
    === "POST"
) {

    if (
        !isset($_FILES["csv_file"])
        || $_FILES["csv_file"]["error"] !== UPLOAD_ERR_OK
    ) {

        $error =
            "Please choose a valid CSV file to upload.";

    } else {

        $handle =
            fopen($_FILES["csv_file"]["tmp_name"], "r");

        if (!$handle) {

            $error =
                "Could not read the uploaded file.";

        } else {

            $header =
                fgetcsv($handle);

            if (!$header) {

                $error =
                    "The CSV file appears to be empty.";

            } else {

                // Normalize header names (trim, lowercase)
                $header = array_map(
                    fn($col) => strtolower(trim($col)),
                    $header
                );

                $requiredColumns = [
                    "name",
                    "price",
                    "stock",
                    "category_name",
                ];

                $missingColumns =
                    array_diff($requiredColumns, $header);

                if (!empty($missingColumns)) {

                    $error =
                        "CSV is missing required column(s): "
                        . implode(", ", $missingColumns)
                        . ". Required columns are: name, description, price, stock, sku, category_name, status, featured.";

                } else {

                    $rowNumber = 1;

                    while (
                        ($row = fgetcsv($handle)) !== false
                    ) {

                        $rowNumber++;

                        if (
                            count($row) === 1
                            && trim($row[0]) === ""
                        ) {
                            continue;
                        }

                        $rowData =
                            array_combine(
                                $header,
                                array_pad($row, count($header), "")
                            );

                        $name =
                            trim($rowData["name"] ?? "");

                        $description =
                            trim($rowData["description"] ?? "");

                        $price =
                            trim($rowData["price"] ?? "");

                        $stock =
                            trim($rowData["stock"] ?? "0");

                        $sku =
                            trim($rowData["sku"] ?? "");

                        $categoryName =
                            trim($rowData["category_name"] ?? "");

                        $status =
                            strtolower(trim($rowData["status"] ?? "active"));

                        $featured =
                            in_array(
                                trim($rowData["featured"] ?? "0"),
                                ["1", "yes", "true"],
                                true
                            ) ? 1 : 0;


                        /*
                        |--------------------------------------------------------------------------
                        | VALIDATE ROW
                        |--------------------------------------------------------------------------
                        */

                        $rowError = "";

                        if ($name === "") {

                            $rowError =
                                "Product name is required.";

                        } elseif (
                            !is_numeric($price)
                            || (float) $price < 0
                        ) {

                            $rowError =
                                "Invalid price.";

                        } elseif (
                            !is_numeric($stock)
                            || (int) $stock < 0
                        ) {

                            $rowError =
                                "Invalid stock quantity.";

                        } elseif ($categoryName === "") {

                            $rowError =
                                "Category name is required.";

                        } elseif (
                            !in_array($status, ["active", "inactive"], true)
                        ) {

                            $rowError =
                                "Status must be 'active' or 'inactive'.";

                        }


                        /*
                        |--------------------------------------------------------------------------
                        | LOOK UP CATEGORY
                        |--------------------------------------------------------------------------
                        */

                        $categoryId = null;

                        if ($rowError === "") {

                            $categoryStmt =
                                $pdo->prepare(
                                    "SELECT id
                                     FROM categories
                                     WHERE name = ?"
                                );

                            $categoryStmt->execute([$categoryName]);

                            $categoryId =
                                $categoryStmt->fetchColumn();

                            if (!$categoryId) {

                                $rowError =
                                    "Category \"" . $categoryName . "\" does not exist. Create it first in Admin > Categories.";

                            }

                        }


                        if ($rowError !== "") {

                            $failedCount++;

                            $results[] = [
                                "row" => $rowNumber,
                                "name" => $name !== "" ? $name : "(blank)",
                                "status" => "failed",
                                "message" => $rowError,
                            ];

                            continue;

                        }


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
                            . "-"
                            . bin2hex(random_bytes(5));


                        /*
                        |--------------------------------------------------------------------------
                        | GENERATE SKU (IF NOT PROVIDED)
                        |--------------------------------------------------------------------------
                        */

                        if ($sku === "") {

                            $namePrefix =
                                strtoupper(
                                    substr(
                                        preg_replace("/[^A-Za-z0-9]/", "", $name),
                                        0,
                                        4
                                    )
                                );

                            $prefix =
                                $namePrefix !== ""
                                    ? $namePrefix
                                    : "PROD";

                            $sku =
                                $prefix
                                . "-"
                                . strtoupper(bin2hex(random_bytes(4)));

                        } else {

                            // If a SKU was supplied, make sure it's not already taken.
                            $skuCheck =
                                $pdo->prepare(
                                    "SELECT id FROM products WHERE sku = ?"
                                );

                            $skuCheck->execute([$sku]);

                            if ($skuCheck->fetch()) {

                                $failedCount++;

                                $results[] = [
                                    "row" => $rowNumber,
                                    "name" => $name,
                                    "status" => "failed",
                                    "message" => "SKU \"" . $sku . "\" already exists.",
                                ];

                                continue;

                            }

                        }


                        /*
                        |--------------------------------------------------------------------------
                        | INSERT
                        |--------------------------------------------------------------------------
                        */

                        $insertStmt =
                            $pdo->prepare(
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
                                    status,
                                    featured
                                )

                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                            );

                        $insertStmt->execute([
                            $vendorId,
                            $categoryId,
                            $name,
                            $slug,
                            $description !== "" ? $description : null,
                            (float) $price,
                            (int) $stock,
                            $sku,
                            $status,
                            $featured,
                        ]);

                        $importedCount++;

                        $results[] = [
                            "row" => $rowNumber,
                            "name" => $name,
                            "status" => "success",
                            "message" => "Imported successfully.",
                        ];

                    }

                }

            }

            fclose($handle);

        }

    }

}


$pageTitle =
    "Import Products | "
    . APP_NAME;


require_once "../includes/header.php";

?>


<div class="mb-4">

    <h1 class="fw-bold">
        Import Products (CSV)
    </h1>

    <p class="text-muted mb-0">
        Bulk add products to your store by uploading a CSV file.
    </p>

</div>


<?php if ($error !== ""): ?>

    <div class="alert alert-danger">
        <?= e($error) ?>
    </div>

<?php endif; ?>


<?php if (!empty($results)): ?>

    <div class="alert <?= $failedCount > 0 ? "alert-warning" : "alert-success" ?>">

        <strong>
            Import finished:
        </strong>

        <?= (int) $importedCount ?> product(s) imported successfully,
        <?= (int) $failedCount ?> failed.

    </div>

    <div class="card border-0 shadow-sm mb-4">

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-sm table-hover align-middle">

                    <thead>
                        <tr>
                            <th>Row</th>
                            <th>Product Name</th>
                            <th>Result</th>
                            <th>Message</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($results as $result): ?>

                            <tr>
                                <td><?= (int) $result["row"] ?></td>
                                <td><?= e($result["name"]) ?></td>
                                <td>
                                    <?php if ($result["status"] === "success"): ?>
                                        <span class="badge bg-success">Success</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Failed</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($result["message"]) ?></td>
                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

<?php endif; ?>


<div class="card border-0 shadow-sm">

    <div class="card-body">

        <h5 class="fw-bold mb-3">Upload CSV</h5>

        <p class="text-muted">
            Your CSV must include a header row with these columns:
        </p>

        <ul class="text-muted">
            <li><code>name</code> — required</li>
            <li><code>description</code> — optional</li>
            <li><code>price</code> — required, number</li>
            <li><code>stock</code> — required, whole number</li>
            <li><code>sku</code> — optional (auto-generated if left blank)</li>
            <li><code>category_name</code> — required, must match an existing category exactly</li>
            <li><code>status</code> — optional, "active" or "inactive" (defaults to active)</li>
            <li><code>featured</code> — optional, 1 or 0 (defaults to 0)</li>
        </ul>

        <form
            method="POST"
            enctype="multipart/form-data"
            class="mt-4"
        >

            <div class="mb-3">
                <input
                    type="file"
                    name="csv_file"
                    accept=".csv"
                    class="form-control"
                    required
                >
            </div>

            <button
                type="submit"
                class="btn btn-dark"
            >
                Upload &amp; Import
            </button>

            <a
                href="<?= BASE_URL ?>vendor/products.php"
                class="btn btn-outline-secondary"
            >
                Back to Products
            </a>

        </form>

    </div>

</div>


<?php

require_once "../includes/footer.php";