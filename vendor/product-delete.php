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

    die(
        "Invalid product ID."
    );

}


/*
|--------------------------------------------------------------------------
| FETCH PRODUCT
|--------------------------------------------------------------------------
|
| IMPORTANT:
| vendor_id ensures the vendor can
| only delete their own product.
|
*/

$stmt = $pdo->prepare(
    "SELECT id, image
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


if (!$product) {

    http_response_code(404);

    die(
        "Product not found or you do not have permission to delete it."
    );

}


/*
|--------------------------------------------------------------------------
| DELETE PRODUCT
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare(
        "DELETE FROM products

         WHERE id = ?

         AND vendor_id = ?"
    );


    $stmt->execute([

        $productId,

        $vendorId

    ]);


    /*
    |--------------------------------------------------------------------------
    | DELETE IMAGE FILE
    |--------------------------------------------------------------------------
    */

    if (
        !empty(
            $product["image"]
        )
    ) {

        $imagePath =
            __DIR__
            . "/../uploads/products/"
            . $product["image"];


        if (
            file_exists(
                $imagePath
            )
        ) {

            unlink(
                $imagePath
            );

        }

    }


    redirect(
        BASE_URL
        . "vendor/products.php"
    );


} catch (
    PDOException $e
) {

    die(
        "Unable to delete product."
    );

}