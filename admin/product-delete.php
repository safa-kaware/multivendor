<?php

require_once "../config/app.php";

requireAdmin();

$productId = isset($_GET["id"])
    ? (int) $_GET["id"]
    : 0;

if ($productId <= 0) {
    http_response_code(400);
    die("Invalid product ID.");
}

/*
|--------------------------------------------------------------------------
| CHECK PRODUCT EXISTS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT id
     FROM products
     WHERE id = ?
     LIMIT 1"
);

$stmt->execute([
    $productId
]);

if (!$stmt->fetch()) {
    http_response_code(404);
    die("Product not found.");
}

/*
|--------------------------------------------------------------------------
| DELETE PRODUCT
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "DELETE FROM products
     WHERE id = ?"
);

$stmt->execute([
    $productId
]);

/*
|--------------------------------------------------------------------------
| REDIRECT
|--------------------------------------------------------------------------
*/

redirect(
    BASE_URL . "admin/products.php"
);