<?php

require_once "../config/app.php";

requireAdmin();

$id =
    (int)
    ($_GET["id"] ?? 0);

if ($id > 0) {

    $stmt =
        $pdo->prepare(
            "DELETE FROM coupons
             WHERE id = ?"
        );

    $stmt->execute([$id]);

}

redirect(
    BASE_URL
    . "admin/coupons.php"
);