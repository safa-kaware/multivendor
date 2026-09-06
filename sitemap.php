<?php

require_once "config/app.php";

header("Content-Type: application/xml; charset=utf-8");


/*
|--------------------------------------------------------------------------
| STATIC PAGES
|--------------------------------------------------------------------------
*/

$staticPages = [
    ["url" => "index.php", "priority" => "1.0", "changefreq" => "daily"],
    ["url" => "search.php", "priority" => "0.8", "changefreq" => "daily"],
    ["url" => "contact.php", "priority" => "0.3", "changefreq" => "monthly"],
    ["url" => "login.php", "priority" => "0.2", "changefreq" => "yearly"],
    ["url" => "signup.php", "priority" => "0.2", "changefreq" => "yearly"],
];


/*
|--------------------------------------------------------------------------
| ACTIVE PRODUCTS
|--------------------------------------------------------------------------
*/

$productsStmt =
    $pdo->query(
        "SELECT slug, updated_at
         FROM products
         WHERE status = 'active'
         ORDER BY updated_at DESC"
    );

$products =
    $productsStmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| ACTIVE CATEGORIES
|--------------------------------------------------------------------------
*/

$categoriesStmt =
    $pdo->query(
        "SELECT id, slug
         FROM categories
         WHERE status = 'active'"
    );

$categories =
    $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| BUILD XML
|--------------------------------------------------------------------------
*/

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

<?php foreach ($staticPages as $page): ?>
    <url>
        <loc><?= BASE_URL . $page["url"] ?></loc>
        <changefreq><?= $page["changefreq"] ?></changefreq>
        <priority><?= $page["priority"] ?></priority>
    </url>
<?php endforeach; ?>

<?php foreach ($categories as $category): ?>
    <url>
        <loc><?= BASE_URL . "search.php?category_id=" . (int) $category["id"] ?></loc>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
<?php endforeach; ?>

<?php foreach ($products as $product): ?>
    <url>
        <loc><?= BASE_URL . "product.php?slug=" . urlencode($product["slug"]) ?></loc>
        <lastmod><?= date("Y-m-d", strtotime($product["updated_at"])) ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.6</priority>
    </url>
<?php endforeach; ?>

</urlset>