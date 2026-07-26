<?php

require_once "../config/app.php";

requireAdmin();

$productId = isset($_GET["id"])
    ? (int) $_GET["id"]
    : 0;

$featured = isset($_GET["featured"])
    ? (int) $_GET["featured"]
    : -1;

if ($productId <= 0) {
    http_response_code(400);
    die("Invalid product ID.");
}

if (!in_array($featured, [0, 1], true)) {
    http_response_code(400);
    die("Invalid featured status.");
}

/*
|--------------------------------------------------------------------------
| CHECK PRODUCT
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
| UPDATE FEATURED STATUS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "UPDATE products
     SET featured = ?
     WHERE id = ?"
);

$stmt->execute([
    $featured,
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