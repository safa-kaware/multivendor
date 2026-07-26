<?php

require_once "../config/app.php";

requireVendor($pdo);

$vendorId = currentVendorId($pdo);

if (!$vendorId) {

    http_response_code(403);

    die("Access denied.");

}


/*
|--------------------------------------------------------------------------
| GET PARAMETERS
|--------------------------------------------------------------------------
*/

$orderId =
    isset($_GET["order_id"])
        ? (int) $_GET["order_id"]
        : 0;

$status =
    $_GET["status"]
    ?? "";


/*
|--------------------------------------------------------------------------
| VALIDATE REQUEST
|--------------------------------------------------------------------------
*/

if ($orderId <= 0) {

    $_SESSION["vendor_error"] =
        "Invalid order.";

    redirect(
        BASE_URL . "vendor/orders.php"
    );

}


/*
|--------------------------------------------------------------------------
| ALLOWED STATUSES
|--------------------------------------------------------------------------
*/

$allowedStatuses = [

    "pending",

    "processing",

    "shipped",

    "delivered",

    "cancelled"

];


if (
    !in_array(
        $status,
        $allowedStatuses,
        true
    )
) {

    $_SESSION["vendor_error"] =
        "Invalid order status.";

    redirect(
        BASE_URL
        . "vendor/order-view.php?id="
        . $orderId
    );

}


/*
|--------------------------------------------------------------------------
| GET VENDOR ORDER ITEMS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(

    "SELECT

        oi.id,
        oi.order_id,
        oi.product_id,
        oi.vendor_id,
        oi.item_status,
        oi.quantity,
        oi.price,

        p.name AS product_name

     FROM order_items oi

     INNER JOIN products p
        ON p.id = oi.product_id

     WHERE oi.order_id = ?

     AND oi.vendor_id = ?

     ORDER BY oi.id ASC"

);

$stmt->execute([

    $orderId,

    $vendorId

]);


$vendorItems =
    $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| VERIFY VENDOR ACCESS
|--------------------------------------------------------------------------
*/

if (
    empty($vendorItems)
) {

    http_response_code(403);

    die(
        "Access denied. You are not authorized to manage this order."
    );

}


/*
|--------------------------------------------------------------------------
| STATUS TRANSITION FUNCTION
|--------------------------------------------------------------------------
*/

function isValidStatusTransition(
    string $currentStatus,
    string $newStatus
): bool {

    $transitions = [

        "pending" => [

            "processing",

            "cancelled"

        ],

        "processing" => [

            "shipped",

            "cancelled"

        ],

        "shipped" => [

            "delivered"

        ],

        "delivered" => [],

        "cancelled" => []

    ];


    if (
        !isset(
            $transitions[
                $currentStatus
            ]
        )
    ) {

        return false;

    }


    return in_array(

        $newStatus,

        $transitions[
            $currentStatus
        ],

        true

    );

}


/*
|--------------------------------------------------------------------------
| UPDATE VENDOR ITEMS
|--------------------------------------------------------------------------
*/

try {

    $pdo->beginTransaction();


    /*
    |--------------------------------------------------------------------------
    | Lock vendor order items
    |--------------------------------------------------------------------------
    */

    $lockStmt = $pdo->prepare(

        "SELECT

            id,
            item_status

         FROM order_items

         WHERE order_id = ?

         AND vendor_id = ?

         FOR UPDATE"

    );


    $lockStmt->execute([

        $orderId,

        $vendorId

    ]);


    $lockedItems =
        $lockStmt->fetchAll();


    if (
        empty($lockedItems)
    ) {

        throw new Exception(

            "No order items found for this vendor."

        );

    }


    /*
    |--------------------------------------------------------------------------
    | Validate each item transition
    |--------------------------------------------------------------------------
    */

    foreach (
        $lockedItems
        as $item
    ) {

        $currentStatus =
            $item[
                "item_status"
            ];


        /*
        |----------------------------------------------------------------------
        | Skip items already at requested status
        |----------------------------------------------------------------------
        */

        if (
            $currentStatus
            ===
            $status
        ) {

            continue;

        }


        if (
            !isValidStatusTransition(

                $currentStatus,

                $status

            )
        ) {

            throw new Exception(

                "Cannot change item status from "
                . ucfirst(
                    $currentStatus
                )
                . " to "
                . ucfirst(
                    $status
                )
                . "."

            );

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Update only this vendor's items
    |--------------------------------------------------------------------------
    */

    $updateStmt = $pdo->prepare(

        "UPDATE order_items

         SET item_status = ?

         WHERE order_id = ?

         AND vendor_id = ?"

    );


    $updateStmt->execute([

        $status,

        $orderId,

        $vendorId

    ]);


    /*
    |--------------------------------------------------------------------------
    | Calculate overall order status
    |--------------------------------------------------------------------------
    */

    $statusStmt = $pdo->prepare(

        "SELECT

            item_status,

            COUNT(*) AS total

         FROM order_items

         WHERE order_id = ?

         GROUP BY item_status"

    );


    $statusStmt->execute([

        $orderId

    ]);


    $statusRows =
        $statusStmt->fetchAll();


    $statusCounts = [];


    foreach (
        $statusRows
        as $row
    ) {

        $statusCounts[
            $row[
                "item_status"
            ]
        ] =
            (int)
            $row[
                "total"
            ];

    }


    /*
    |--------------------------------------------------------------------------
    | Determine overall order status
    |--------------------------------------------------------------------------
    */

    $overallStatus =
        "pending";


    /*
    |--------------------------------------------------------------------------
    | All delivered
    |--------------------------------------------------------------------------
    */

    if (
        isset(
            $statusCounts[
                "delivered"
            ]
        )

        &&

        $statusCounts[
            "delivered"
        ]

        ===

        array_sum(
            $statusCounts
        )

    ) {

        $overallStatus =
            "delivered";

    }


    /*
    |--------------------------------------------------------------------------
    | All cancelled
    |--------------------------------------------------------------------------
    */

    elseif (
        isset(
            $statusCounts[
                "cancelled"
            ]
        )

        &&

        $statusCounts[
            "cancelled"
        ]

        ===

        array_sum(
            $statusCounts
        )

    ) {

        $overallStatus =
            "cancelled";

    }


    /*
    |--------------------------------------------------------------------------
    | Any shipped
    |--------------------------------------------------------------------------
    */

    elseif (
        isset(
            $statusCounts[
                "shipped"
            ]
        )

        &&

        $statusCounts[
            "shipped"
        ]
        > 0

    ) {

        $overallStatus =
            "shipped";

    }


    /*
    |--------------------------------------------------------------------------
    | Any processing
    |--------------------------------------------------------------------------
    */

    elseif (
        isset(
            $statusCounts[
                "processing"
            ]
        )

        &&

        $statusCounts[
            "processing"
        ]
        > 0

    ) {

        $overallStatus =
            "processing";

    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE MAIN ORDER STATUS
    |--------------------------------------------------------------------------
    */

    $orderUpdateStmt = $pdo->prepare(

        "UPDATE orders

         SET order_status = ?

         WHERE id = ?"

    );


    $orderUpdateStmt->execute([

        $overallStatus,

        $orderId

    ]);


    /*
    |--------------------------------------------------------------------------
    | COMMIT
    |--------------------------------------------------------------------------
    */

    $pdo->commit();


    /*
    |--------------------------------------------------------------------------
    | SUCCESS
    |--------------------------------------------------------------------------
    */

    $_SESSION["vendor_success"] =

        "Your order items have been updated to "
        . ucfirst(
            $status
        )
        . ".";


    redirect(

        BASE_URL

        . "vendor/order-view.php?id="

        . $orderId

    );


} catch (
    Exception $e
) {


    /*
    |--------------------------------------------------------------------------
    | ROLLBACK
    |--------------------------------------------------------------------------
    */

    if (
        $pdo->inTransaction()
    ) {

        $pdo->rollBack();

    }


    $_SESSION["vendor_error"] =
        $e->getMessage();


    redirect(

        BASE_URL

        . "vendor/order-view.php?id="

        . $orderId

    );

}