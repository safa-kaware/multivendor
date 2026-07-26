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
    | Reject Vendor
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare(
        "UPDATE vendors

         SET status = 'rejected'

         WHERE id = ?"
    );

    $stmt->execute([
        $vendorId
    ]);


    /*
    |--------------------------------------------------------------------------
    | Change User Back To Customer
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare(
        "UPDATE users

         SET role = 'customer'

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
        "Unable to reject vendor."
    );

}


redirect(
    BASE_URL
    . "admin/vendors.php"
);