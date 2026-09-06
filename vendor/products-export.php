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


/*
|--------------------------------------------------------------------------
| FETCH VENDOR'S PRODUCTS
|--------------------------------------------------------------------------
*/

$stmt =
    $pdo->prepare(
        "SELECT
            p.name,
            p.description,
            p.price,
            p.stock,
            p.sku,
            c.name AS category_name,
            p.status,
            p.featured

         FROM products p

         INNER JOIN categories c
            ON c.id = p.category_id

         WHERE p.vendor_id = ?

         ORDER BY p.created_at DESC"
    );

$stmt->execute([$vendorId]);

$products =
    $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| OUTPUT CSV
|--------------------------------------------------------------------------
*/

header("Content-Type: text/csv; charset=utf-8");

header(
    "Content-Disposition: attachment; filename=my-products-"
    . date("Y-m-d")
    . ".csv"
);

$output = fopen("php://output", "w");

// Byte-order mark so Excel opens UTF-8 correctly
fwrite($output, "\xEF\xBB\xBF");

fputcsv($output, [
    "name",
    "description",
    "price",
    "stock",
    "sku",
    "category_name",
    "status",
    "featured",
]);

foreach ($products as $product) {

    fputcsv($output, [
        $product["name"],
        $product["description"],
        $product["price"],
        $product["stock"],
        $product["sku"],
        $product["category_name"],
        $product["status"],
        $product["featured"] ? "1" : "0",
    ]);

}

fclose($output);

exit;