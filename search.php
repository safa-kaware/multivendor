<?php

require_once "config/app.php";


/*
|--------------------------------------------------------------------------
| PAGE SETTINGS
|--------------------------------------------------------------------------
*/

$pageTitle = "Search Products | " . APP_NAME;


/*
|--------------------------------------------------------------------------
| GET FILTER VALUES
|--------------------------------------------------------------------------
*/

$search = trim(
    $_GET["q"] ?? ""
);


$categoryId = (int) (
    $_GET["category_id"]
    ?? 0
);


$minPrice = trim(
    $_GET["min_price"]
    ?? ""
);


$maxPrice = trim(
    $_GET["max_price"]
    ?? ""
);


$minRating = (int) (
    $_GET["min_rating"]
    ?? 0
);


$sort = $_GET["sort"]
    ?? "newest";


$page = max(
    1,
    (int) (
        $_GET["page"]
        ?? 1
    )
);


/*
|--------------------------------------------------------------------------
| PRODUCTS PER PAGE
|--------------------------------------------------------------------------
*/

$perPage = 12;

$offset =
    ($page - 1)
    * $perPage;


/*
|--------------------------------------------------------------------------
| GET CATEGORIES
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT
        id,
        name,
        slug

     FROM categories

     WHERE status = 'active'

     ORDER BY name ASC"
);

$stmt->execute();

$categories =
    $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| BUILD WHERE CONDITIONS
|--------------------------------------------------------------------------
*/

$where = [

    "p.status = 'active'",

    "v.status = 'approved'"

];


$params = [];


/*
|--------------------------------------------------------------------------
| SEARCH FILTER
|--------------------------------------------------------------------------
*/

if (
    $search !== ""
) {

    $where[] = "

        (
            p.name LIKE ?

            OR p.description LIKE ?

            OR p.sku LIKE ?

        )

    ";


    $searchTerm =
        "%" . $search . "%";


    $params[] =
        $searchTerm;

    $params[] =
        $searchTerm;

    $params[] =
        $searchTerm;

}


/*
|--------------------------------------------------------------------------
| CATEGORY FILTER
|--------------------------------------------------------------------------
*/

if (
    $categoryId > 0
) {

    $where[] =
        "p.category_id = ?";


    $params[] =
        $categoryId;

}


/*
|--------------------------------------------------------------------------
| MINIMUM PRICE
|--------------------------------------------------------------------------
*/

if (
    $minPrice !== ""
    &&
    is_numeric(
        $minPrice
    )
) {

    $where[] =
        "p.price >= ?";


    $params[] =
        (float)
        $minPrice;

}


/*
|--------------------------------------------------------------------------
| MAXIMUM PRICE
|--------------------------------------------------------------------------
*/

if (
    $maxPrice !== ""
    &&
    is_numeric(
        $maxPrice
    )
) {

    $where[] =
        "p.price <= ?";


    $params[] =
        (float)
        $maxPrice;

}


/*
|--------------------------------------------------------------------------
| RATING FILTER
|--------------------------------------------------------------------------
|
| Products without reviews are treated as 0 rating.
|
*/

if (
    $minRating >= 1
    &&
    $minRating <= 5
) {

    $where[] = "

        COALESCE(
            (
                SELECT AVG(r.rating)

                FROM reviews r

                WHERE r.product_id = p.id

            ),
            0
        ) >= ?

    ";


    $params[] =
        $minRating;

}


/*
|--------------------------------------------------------------------------
| SORTING
|--------------------------------------------------------------------------
*/

$allowedSorts = [

    "newest" =>
        "p.created_at DESC",

    "price_low" =>
        "p.price ASC",

    "price_high" =>
        "p.price DESC",

    "featured" =>
        "p.featured DESC, p.created_at DESC"

];


if (
    !isset(
        $allowedSorts[
            $sort
        ]
    )
) {

    $sort =
        "newest";

}


$orderBy =
    $allowedSorts[
        $sort
    ];


/*
|--------------------------------------------------------------------------
| WHERE SQL
|--------------------------------------------------------------------------
*/

$whereSql =
    implode(
        " AND ",
        $where
    );


/*
|--------------------------------------------------------------------------
| COUNT PRODUCTS
|--------------------------------------------------------------------------
*/

$countSql = "

    SELECT
        COUNT(*)

    FROM products p

    INNER JOIN vendors v
        ON p.vendor_id = v.id

    WHERE
        $whereSql

";


$countStmt =
    $pdo->prepare(
        $countSql
    );


$countStmt->execute(
    $params
);


$totalProducts =
    (int)
    $countStmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| TOTAL PAGES
|--------------------------------------------------------------------------
*/

$totalPages =
    max(
        1,
        (int)
        ceil(
            $totalProducts
            /
            $perPage
        )
    );


/*
|--------------------------------------------------------------------------
| PREVENT INVALID PAGE
|--------------------------------------------------------------------------
*/

if (
    $page > $totalPages
) {

    $page =
        $totalPages;


    $offset =
        ($page - 1)
        * $perPage;

}


/*
|--------------------------------------------------------------------------
| FETCH PRODUCTS
|--------------------------------------------------------------------------
*/

$sql = "

    SELECT

        p.id,

        p.name,

        p.slug,

        p.description,

        p.price,

        p.stock,

        p.image,

        p.featured,

        p.created_at,

        c.name AS category_name,

        v.store_name,

        COALESCE(
            (
                SELECT AVG(r.rating)

                FROM reviews r

                WHERE r.product_id = p.id

            ),
            0
        ) AS average_rating,

        COALESCE(
            (
                SELECT COUNT(*)

                FROM reviews r

                WHERE r.product_id = p.id

            ),
            0
        ) AS review_count

    FROM products p

    INNER JOIN categories c
        ON p.category_id = c.id

    INNER JOIN vendors v
        ON p.vendor_id = v.id

    WHERE

        $whereSql

    ORDER BY

        $orderBy

    LIMIT ?

    OFFSET ?

";


$stmt =
    $pdo->prepare(
        $sql
    );


/*
|--------------------------------------------------------------------------
| BIND FILTER PARAMETERS
|--------------------------------------------------------------------------
*/

$paramIndex = 1;


foreach (
    $params
    as $param
) {

    $stmt->bindValue(
        $paramIndex,
        $param
    );


    $paramIndex++;

}


/*
|--------------------------------------------------------------------------
| BIND PAGINATION
|--------------------------------------------------------------------------
*/

$stmt->bindValue(
    $paramIndex,
    $perPage,
    PDO::PARAM_INT
);


$stmt->bindValue(
    $paramIndex + 1,
    $offset,
    PDO::PARAM_INT
);


$stmt->execute();


$products =
    $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| BUILD PAGINATION URL
|--------------------------------------------------------------------------
*/

function searchUrl(
    $pageNumber
) {

    $params = $_GET;


    $params["page"] =
        $pageNumber;


    return
        "search.php?"
        .
        http_build_query(
            $params
        );

}


require_once "includes/header.php";

?>


<div class="container py-5">


<!-- PAGE HEADER -->

<div class="mb-4">

<h1 class="fw-bold">

Search Products

</h1>


<?php if (
    $search !== ""
): ?>

<p class="text-muted">

Search results for:

<strong>

<?= e(
    $search
) ?>

</strong>

</p>

<?php else: ?>

<p class="text-muted">

Browse our products and find what you need.

</p>

<?php endif; ?>

</div>



<div class="row g-4">


<!-- FILTER SIDEBAR -->

<div class="col-lg-3">


<div class="card border-0 shadow-sm">


<div class="card-body">


<h5 class="fw-bold mb-4">

Filters

</h5>


<form
    method="GET"
    action="search.php"
>


<!-- SEARCH -->

<div class="mb-3">


<label
    class="form-label"
>

Search

</label>


<input
    type="text"
    name="q"
    class="form-control"
    value="<?= e(
        $search
    ) ?>"
    placeholder="Search products..."
>


</div>



<!-- CATEGORY -->

<div class="mb-3">


<label
    class="form-label"
>

Category

</label>


<select
    name="category_id"
    class="form-select"
>


<option
    value="0"
>

All Categories

</option>


<?php foreach (
    $categories
    as $category
): ?>


<option
    value="<?= (int)
        $category["id"] ?>"
    <?= $categoryId
        ===
        (int)
        $category["id"]
        ? "selected"
        : "" ?>
>

<?= e(
    $category["name"]
) ?>

</option>


<?php endforeach; ?>


</select>


</div>



<!-- MIN PRICE -->

<div class="mb-3">


<label
    class="form-label"
>

Minimum Price

</label>


<input
    type="number"
    name="min_price"
    class="form-control"
    min="0"
    step="0.01"
    value="<?= e(
        $minPrice
    ) ?>"
    placeholder="₹0"
>


</div>



<!-- MAX PRICE -->

<div class="mb-3">


<label
    class="form-label"
>

Maximum Price

</label>


<input
    type="number"
    name="max_price"
    class="form-control"
    min="0"
    step="0.01"
    value="<?= e(
        $maxPrice
    ) ?>"
    placeholder="₹10000"
>


</div>



<!-- RATING -->

<div class="mb-3">


<label
    class="form-label"
>

Minimum Rating

</label>


<select
    name="min_rating"
    class="form-select"
>


<option
    value="0"
>

Any Rating

</option>


<?php for (
    $rating = 5;
    $rating >= 1;
    $rating--
): ?>


<option
    value="<?= $rating ?>"
    <?= $minRating
        === $rating
        ? "selected"
        : "" ?>
>

<?= $rating ?>

★ & above

</option>


<?php endfor; ?>


</select>


</div>



<!-- SORT -->

<div class="mb-3">


<label
    class="form-label"
>

Sort By

</label>


<select
    name="sort"
    class="form-select"
>


<option
    value="newest"
    <?= $sort
        === "newest"
        ? "selected"
        : "" ?>
>

Newest

</option>


<option
    value="featured"
    <?= $sort
        === "featured"
        ? "selected"
        : "" ?>
>

Featured

</option>


<option
    value="price_low"
    <?= $sort
        === "price_low"
        ? "selected"
        : "" ?>
>

Price: Low to High

</option>


<option
    value="price_high"
    <?= $sort
        === "price_high"
        ? "selected"
        : "" ?>
>

Price: High to Low

</option>


</select>


</div>



<button
    type="submit"
    class="btn btn-dark w-100"
>

Apply Filters

</button>


<a
    href="<?= BASE_URL ?>search.php"
    class="btn btn-outline-secondary w-100 mt-2"
>

Clear Filters

</a>


</form>


</div>


</div>


</div>



<!-- PRODUCTS -->

<div class="col-lg-9">


<div
    class="d-flex justify-content-between align-items-center mb-4"
>


<div>

<strong>

<?= $totalProducts ?>

</strong>

product(s) found

</div>


</div>



<?php if (
    empty($products)
): ?>


<div class="alert alert-info">

No products found matching your filters.

</div>


<?php else: ?>


<div class="row g-4">


<?php foreach (
    $products
    as $product
): ?>


<div class="col-md-6 col-xl-4">


<div
    class="card h-100 border-0 shadow-sm"
>


<!-- IMAGE -->


<a
    href="<?= BASE_URL ?>product.php?slug=<?= urlencode(
        $product["slug"]
    ) ?>"
>


<?php if (
    !empty(
        $product["image"]
    )
): ?>


<img
    src="<?= e(
        productImageUrl($product["image"])
    ) ?>"
    class="card-img-top"


<?php else: ?>


<div
    class="bg-light d-flex align-items-center justify-content-center"
    style="
        height:220px;
    "
>

<i
    class="bi bi-image fs-1 text-muted"
></i>

</div>


<?php endif; ?>


</a>



<div
    class="card-body"
>


<!-- CATEGORY -->

<small
    class="text-muted"
>

<?= e(
    $product["category_name"]
) ?>

</small>


<h5
    class="card-title mt-2"
>

<a
    href="<?= BASE_URL ?>product.php?slug=<?= urlencode(
        $product["slug"]
    ) ?>"
    class="text-decoration-none text-dark"
>

<?= e(
    $product["name"]
) ?>

</a>

</h5>


<!-- VENDOR -->

<small
    class="text-muted"
>

Sold by:

<?= e(
    $product["store_name"]
) ?>

</small>



<!-- RATING -->

<div
    class="mt-2"
>


<span
    class="text-warning"
>

<?php

$rating =
    round(
        (float)
        $product[
            "average_rating"
        ]
    );

?>


<?php for (
    $i = 1;
    $i <= 5;
    $i++
): ?>


<?= $i <= $rating
    ? "★"
    : "☆" ?>


<?php endfor; ?>


</span>


<small
    class="text-muted"
>

(

<?= (int)
    $product[
        "review_count"
    ] ?>

)

</small>


</div>



<!-- PRICE -->

<h5
    class="fw-bold mt-3"
>

₹<?= number_format(
    $product["price"],
    2
) ?>

</h5>


<?php if (
    (int)
    $product["stock"]
    > 0
): ?>


<span
    class="badge text-bg-success"
>

In Stock

</span>


<?php else: ?>


<span
    class="badge text-bg-danger"
>

Out of Stock

</span>


<?php endif; ?>


</div>


<div
    class="card-footer bg-white border-0 pb-3"
>


<a
    href="<?= BASE_URL ?>product.php?slug=<?= urlencode(
        $product["slug"]
    ) ?>"
    class="btn btn-dark w-100"
>

View Product

</a>


</div>


</div>


</div>


<?php endforeach; ?>


</div>



<!-- PAGINATION -->

<?php if (
    $totalPages > 1
): ?>


<nav
    class="mt-5"
>


<ul
    class="pagination justify-content-center"
>


<!-- PREVIOUS -->

<li
    class="page-item
    <?= $page <= 1
        ? "disabled"
        : "" ?>"
>


<a
    class="page-link"
    href="<?= $page > 1
        ? e(
            searchUrl(
                $page - 1
            )
        )
        : "#" ?>"
>

Previous

</a>


</li>



<?php

$startPage =
    max(
        1,
        $page - 2
    );


$endPage =
    min(
        $totalPages,
        $page + 2
    );

?>


<?php for (
    $i = $startPage;
    $i <= $endPage;
    $i++
): ?>


<li
    class="page-item
    <?= $i === $page
        ? "active"
        : "" ?>"
>


<a
    class="page-link"
    href="<?= e(
        searchUrl(
            $i
        )
    ) ?>"
>

<?= $i ?>

</a>


</li>


<?php endfor; ?>



<!-- NEXT -->

<li
    class="page-item
    <?= $page >= $totalPages
        ? "disabled"
        : "" ?>"
>


<a
    class="page-link"
    href="<?= $page < $totalPages
        ? e(
            searchUrl(
                $page + 1
            )
        )
        : "#" ?>"
>

Next

</a>


</li>


</ul>


</nav>


<?php endif; ?>


<?php endif; ?>


</div>


</div>


</div>


<?php

require_once "includes/footer.php";

?>