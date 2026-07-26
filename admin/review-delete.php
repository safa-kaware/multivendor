<?php

require_once "../config/app.php";

requireAdmin();

$reviewId = isset($_GET["id"])
    ? (int) $_GET["id"]
    : 0;

if ($reviewId <= 0) {
    die("Invalid review ID.");
}

$stmt = $pdo->prepare(
    "DELETE FROM reviews
     WHERE id = ?"
);

$stmt->execute([
    $reviewId
]);

redirect(
    BASE_URL . "admin/reviews.php"
);