<?php

require_once "../config/app.php";

requireAdmin();


$vendorId =
    (int) (
        $_GET["id"]
        ?? 0
    );


if (
    $vendorId <= 0
) {

    redirect(
        BASE_URL
        . "admin/vendors.php"
    );

}


try {


    $pdo->beginTransaction();


    /*
    |--------------------------------------------------------------------------
    | Get Vendor User ID
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare(
        "SELECT user_id

         FROM vendors

         WHERE id = ?

         LIMIT 1"
    );

    $stmt->execute([
        $vendorId
    ]);

    $vendor =
        $stmt->fetch();


    if (
        !$vendor
    ) {

        throw new Exception(
            "Vendor not found."
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Approve Vendor
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare(
        "UPDATE vendors

         SET status = 'approved'

         WHERE id = ?"
    );

    $stmt->execute([
        $vendorId
    ]);


    /*
    |--------------------------------------------------------------------------
    | Ensure User Role Is Vendor
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare(
        "UPDATE users

         SET role = 'vendor',
             status = 'active'

         WHERE id = ?"
    );

    $stmt->execute([
        $vendor["user_id"]
    ]);


    $pdo->commit();


} catch (
    Exception $e
) {


    if (
        $pdo->inTransaction()
    ) {

        $pdo->rollBack();

    }


    die(
        "Unable to approve vendor."
    );

}


redirect(
    BASE_URL
    . "admin/vendors.php"
);