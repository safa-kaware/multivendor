<?php

require_once "../config/app.php";

requireAdmin();

$id =
    (int)
    ($_GET["id"] ?? 0);

if ($id > 0) {

    $stmt =
        $pdo->prepare(
            "SELECT status
             FROM coupons
             WHERE id = ?"
        );

    $stmt->execute([$id]);

    $current =
        $stmt->fetchColumn();

    if ($current !== false) {

        $newStatus =
            $current === "active"
                ? "inactive"
                : "active";

        $update =
            $pdo->prepare(
                "UPDATE coupons
                 SET status = ?
                 WHERE id = ?"
            );

        $update->execute([
            $newStatus,
            $id
        ]);

    }

}

redirect(
    BASE_URL
    . "admin/coupons.php"
);