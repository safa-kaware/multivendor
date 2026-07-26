<?php

require_once "../config/app.php";


requireAdmin();


/*
|--------------------------------------------------------------------------
| Get Category ID
|--------------------------------------------------------------------------
*/

$categoryId =
    (int) (
        $_GET["id"]
        ?? 0
    );


if (
    $categoryId <= 0
) {

    redirect(
        BASE_URL
        . "admin/categories.php"
    );

}


/*
|--------------------------------------------------------------------------
| Check Product Count
|--------------------------------------------------------------------------
*/

$stmt =
    $pdo->prepare(
        "SELECT COUNT(*)

         FROM products

         WHERE category_id = ?"
    );


$stmt->execute([
    $categoryId
]);


$productCount =
    (int) $stmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| Prevent Deletion If Products Exist
|--------------------------------------------------------------------------
*/

if (
    $productCount > 0
) {

    die(
        "Cannot delete this category because "
        . $productCount
        . " product(s) are assigned to it. "
        . "Please move or remove those products first."
    );

}


/*
|--------------------------------------------------------------------------
| Fetch Category Image
|--------------------------------------------------------------------------
*/

$stmt =
    $pdo->prepare(
        "SELECT image

         FROM categories

         WHERE id = ?

         LIMIT 1"
    );


$stmt->execute([
    $categoryId
]);


$category =
    $stmt->fetch();


if (
    !$category
) {

    redirect(
        BASE_URL
        . "admin/categories.php"
    );

}


/*
|--------------------------------------------------------------------------
| Delete Category
|--------------------------------------------------------------------------
*/

$stmt =
    $pdo->prepare(
        "DELETE FROM categories

         WHERE id = ?"
    );


$stmt->execute([
    $categoryId
]);


/*
|--------------------------------------------------------------------------
| Delete Category Image
|--------------------------------------------------------------------------
*/

if (
    !empty(
        $category["image"]
    )
) {


    $imagePath =
        "../uploads/categories/"
        . $category["image"];


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


/*
|--------------------------------------------------------------------------
| Redirect
|--------------------------------------------------------------------------
*/

redirect(
    BASE_URL
    . "admin/categories.php"
);