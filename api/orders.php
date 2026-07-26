<?php

/*
|--------------------------------------------------------------------------
| GET /api/orders.php
|--------------------------------------------------------------------------
|
| Requires: Authorization: Bearer <token>   (get one from /api/auth.php)
|
| Returns the authenticated user's own orders only.
|
| Query params:
|
| id  -> optional, fetch a single order (with its line items) by id.
|        Only returns it if it belongs to the authenticated user.
|
| Examples:
|
| /api/orders.php
| /api/orders.php?id=12
|
*/

require_once __DIR__ . "/config.php";

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    jsonError("Only GET is allowed on this endpoint.", 405);
}

$userId = requireApiAuth();


/*
|--------------------------------------------------------------------------
| SINGLE ORDER (WITH ITEMS)
|--------------------------------------------------------------------------
*/

if (isset($_GET["id"])) {

    $stmt = $pdo->prepare(
        "SELECT
            id,
            total_amount,
            discount_amount,
            shipping_amount,
            payment_method,
            payment_status,
            order_status,
            created_at

         FROM orders

         WHERE id = ?
         AND user_id = ?"
    );

    $stmt->execute([(int) $_GET["id"], $userId]);

    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        jsonError("Order not found.", 404);
    }

    $itemsStmt = $pdo->prepare(
        "SELECT
            oi.id,
            oi.product_id,
            p.name AS product_name,
            p.image,
            oi.quantity,
            oi.price

         FROM order_items oi

         INNER JOIN products p
            ON p.id = oi.product_id

         WHERE oi.order_id = ?"
    );

    $itemsStmt->execute([$order["id"]]);

    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as &$item) {
        $item["price"] = (float) $item["price"];
        $item["quantity"] = (int) $item["quantity"];
        $item["image_url"] = productImageUrl($item["image"]);
    }
    unset($item);

    $order["total_amount"] = (float) $order["total_amount"];
    $order["discount_amount"] = (float) $order["discount_amount"];
    $order["shipping_amount"] = (float) $order["shipping_amount"];
    $order["items"] = $items;

    jsonResponse([
        "success" => true,
        "order" => $order,
    ]);

}


/*
|--------------------------------------------------------------------------
| ORDER LIST
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT
        id,
        total_amount,
        discount_amount,
        shipping_amount,
        payment_method,
        payment_status,
        order_status,
        created_at

     FROM orders

     WHERE user_id = ?

     ORDER BY created_at DESC"
);

$stmt->execute([$userId]);

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($orders as &$order) {
    $order["total_amount"] = (float) $order["total_amount"];
    $order["discount_amount"] = (float) $order["discount_amount"];
    $order["shipping_amount"] = (float) $order["shipping_amount"];
}
unset($order);

jsonResponse([
    "success" => true,
    "orders" => $orders,
]);