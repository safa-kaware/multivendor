<?php

/*
|--------------------------------------------------------------------------
| GET /api/products.php
|--------------------------------------------------------------------------
|
| Public read-only endpoint. Query params (all optional):
|
| id            -> single product by id
| category_id   -> filter by category
| vendor_id     -> filter by vendor
| search        -> match against product name
| limit         -> max results (default 20, max 100)
| page          -> pagination page number (default 1)
|
| Examples:
|
| /api/products.php
| /api/products.php?id=5
| /api/products.php?category_id=2&limit=10
| /api/products.php?search=watch
|
*/

require_once __DIR__ . "/config.php";

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    jsonError("Only GET is allowed on this endpoint.", 405);
}


/*
|--------------------------------------------------------------------------
| SINGLE PRODUCT
|--------------------------------------------------------------------------
*/

if (isset($_GET["id"])) {

    $stmt = $pdo->prepare(
        "SELECT
            p.id,
            p.name,
            p.slug,
            p.description,
            p.price,
            p.stock,
            p.sku,
            p.image,
            p.status,
            p.featured,
            p.created_at,

            c.id AS category_id,
            c.name AS category_name,

            v.id AS vendor_id,
            v.store_name

         FROM products p

         INNER JOIN categories c
            ON c.id = p.category_id

         INNER JOIN vendors v
            ON v.id = p.vendor_id

         WHERE p.id = ?
         AND p.status = 'active'"
    );

    $stmt->execute([(int) $_GET["id"]]);

    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        jsonError("Product not found.", 404);
    }

    $product["price"] = (float) $product["price"];
    $product["stock"] = (int) $product["stock"];
    $product["featured"] = (bool) $product["featured"];
    $product["image_url"] = productImageUrl($product["image"]);

    jsonResponse([
        "success" => true,
        "product" => $product,
    ]);

}


/*
|--------------------------------------------------------------------------
| PRODUCT LIST (WITH FILTERS)
|--------------------------------------------------------------------------
*/

$where = ["p.status = 'active'"];
$params = [];

if (!empty($_GET["category_id"])) {
    $where[] = "p.category_id = ?";
    $params[] = (int) $_GET["category_id"];
}

if (!empty($_GET["vendor_id"])) {
    $where[] = "p.vendor_id = ?";
    $params[] = (int) $_GET["vendor_id"];
}

if (!empty($_GET["search"])) {
    $where[] = "p.name LIKE ?";
    $params[] = "%" . $_GET["search"] . "%";
}

$limit = isset($_GET["limit"]) ? (int) $_GET["limit"] : 20;
$limit = max(1, min($limit, 100));

$page = isset($_GET["page"]) ? (int) $_GET["page"] : 1;
$page = max(1, $page);

$offset = ($page - 1) * $limit;

$whereSql = implode(" AND ", $where);


/*
| Count total for pagination
*/

$countStmt = $pdo->prepare(
    "SELECT COUNT(*) FROM products p WHERE $whereSql"
);

$countStmt->execute($params);

$total = (int) $countStmt->fetchColumn();


/*
| Fetch page of results
*/

$stmt = $pdo->prepare(
    "SELECT
        p.id,
        p.name,
        p.slug,
        p.price,
        p.stock,
        p.image,
        p.featured,

        c.name AS category_name,
        v.store_name

     FROM products p

     INNER JOIN categories c
        ON c.id = p.category_id

     INNER JOIN vendors v
        ON v.id = p.vendor_id

     WHERE $whereSql

     ORDER BY p.created_at DESC

     LIMIT $limit OFFSET $offset"
);

$stmt->execute($params);

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($products as &$product) {
    $product["price"] = (float) $product["price"];
    $product["stock"] = (int) $product["stock"];
    $product["featured"] = (bool) $product["featured"];
    $product["image_url"] = productImageUrl($product["image"]);
}
unset($product);

jsonResponse([
    "success" => true,
    "pagination" => [
        "total" => $total,
        "page" => $page,
        "limit" => $limit,
        "total_pages" => (int) ceil($total / $limit),
    ],
    "products" => $products,
]);