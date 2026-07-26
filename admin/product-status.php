<?php

require_once "../config/app.php";

// Require admin authentication
requireAdmin();

/*
|--------------------------------------------------------------------------
| GET PRODUCT ID
|--------------------------------------------------------------------------
*/

$productId = isset($_GET["id"])
    ? (int) $_GET["id"]
    : 0;


/*
|--------------------------------------------------------------------------
| GET NEW STATUS
|--------------------------------------------------------------------------
*/

$status = $_GET["status"] ?? "";


/*
|--------------------------------------------------------------------------
| VALIDATE PRODUCT ID
|--------------------------------------------------------------------------
*/

if ($productId <= 0) {

    http_response_code(400);

    die("Invalid product ID.");

}


/*
|--------------------------------------------------------------------------
| VALIDATE STATUS
|--------------------------------------------------------------------------
*/

$allowedStatuses = [
    "active",
    "inactive"
];

if (!in_array($status, $allowedStatuses, true)) {

    http_response_code(400);

    die("Invalid product status.");

}


/*
|--------------------------------------------------------------------------
| CHECK PRODUCT EXISTS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT id, name, status
     FROM products
     WHERE id = ?
     LIMIT 1"
);

$stmt->execute([
    $productId
]);

$product = $stmt->fetch();


if (!$product) {

    http_response_code(404);

    die("Product not found.");

}


/*
|--------------------------------------------------------------------------
| UPDATE PRODUCT STATUS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "UPDATE products
     SET status = ?
     WHERE id = ?"
);

$stmt->execute([
    $status,
    $productId
]);


/*
|--------------------------------------------------------------------------
| REDIRECT BACK TO PRODUCTS
|--------------------------------------------------------------------------
*/

redirect(
    BASE_URL . "admin/products.php"
);