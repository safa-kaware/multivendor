<?php


/*
|--------------------------------------------------------------------------
| GENERAL HELPER FUNCTIONS
|--------------------------------------------------------------------------
*/


/**
 * Escape output to prevent XSS attacks.
 */
function e($value)
{
    return htmlspecialchars(
        $value ?? "",
        ENT_QUOTES,
        "UTF-8"
    );
}


/**
 * Redirect user to another page.
 */
function redirect($url)
{
    header(
        "Location: " . $url
    );

    exit;
}


/**
 * Check if the user is logged in.
 */
function isLoggedIn()
{
    return isset(
        $_SESSION["user_id"]
    );
}


/**
 * Get currently logged-in user's ID.
 */
function currentUserId()
{
    return $_SESSION["user_id"]
        ?? null;
}


/**
 * Get currently logged-in user's role.
 */
function currentUserRole()
{
    return $_SESSION["user_role"]
        ?? null;
}


/*
|--------------------------------------------------------------------------
| CART FUNCTIONS
|--------------------------------------------------------------------------
|
| New cart structure:
|
| $_SESSION["cart"] = [
|
|     "unique_cart_key" => [
|
|         "product_id" => 10,
|
|         "quantity" => 2,
|
|         "variation_ids" => [
|             1,
|             4
|         ]
|
|     ]
|
| ];
|
*/


/**
 * Add product to cart.
 *
 * Supports product variations.
 */
function addToCart(
    int $productId,
    int $quantity = 1,
    array $variationIds = []
): void {

    /*
    |--------------------------------------------------------------------------
    | Validate quantity
    |--------------------------------------------------------------------------
    */

    if (
        $quantity < 1
    ) {

        $quantity = 1;

    }


    /*
    |--------------------------------------------------------------------------
    | Initialize cart
    |--------------------------------------------------------------------------
    */

    if (
        !isset(
            $_SESSION["cart"]
        )
        ||
        !is_array(
            $_SESSION["cart"]
        )
    ) {

        $_SESSION["cart"] = [];

    }


    /*
    |--------------------------------------------------------------------------
    | Clean variation IDs
    |--------------------------------------------------------------------------
    */

    $variationIds =
        array_map(
            "intval",
            $variationIds
        );


    $variationIds =
        array_values(
            array_filter(
                $variationIds
            )
        );


    /*
    |--------------------------------------------------------------------------
    | Sort variation IDs
    |--------------------------------------------------------------------------
    |
    | This ensures that:
    |
    | [1, 4]
    |
    | and
    |
    | [4, 1]
    |
    | create the same cart key.
    |
    */

    sort(
        $variationIds
    );


    /*
    |--------------------------------------------------------------------------
    | Generate unique cart key
    |--------------------------------------------------------------------------
    */

    $cartKey =
        $productId
        . "_"
        . implode(
            "-",
            $variationIds
        );


    /*
    |--------------------------------------------------------------------------
    | Existing cart item
    |--------------------------------------------------------------------------
    */

    if (
        isset(
            $_SESSION["cart"][
                $cartKey
            ]
        )
    ) {

        $_SESSION["cart"][
            $cartKey
        ]["quantity"]
            += $quantity;

    } else {


        /*
        |--------------------------------------------------------------------------
        | New cart item
        |--------------------------------------------------------------------------
        */

        $_SESSION["cart"][
            $cartKey
        ] = [

            "product_id"
                =>
            $productId,

            "quantity"
                =>
            $quantity,

            "variation_ids"
                =>
            $variationIds

        ];

    }

}


/**
 * Update product quantity in cart.
 */
function updateCartQuantity(
    string $cartKey,
    int $quantity
): void {

    if (
        !isset(
            $_SESSION["cart"]
        )
    ) {

        return;

    }


    /*
    |--------------------------------------------------------------------------
    | Remove if quantity is zero
    |--------------------------------------------------------------------------
    */

    if (
        $quantity <= 0
    ) {

        unset(
            $_SESSION["cart"][
                $cartKey
            ]
        );

        return;

    }


    /*
    |--------------------------------------------------------------------------
    | Update quantity
    |--------------------------------------------------------------------------
    */

    if (
        isset(
            $_SESSION["cart"][
                $cartKey
            ]
        )
    ) {

        $_SESSION["cart"][
            $cartKey
        ]["quantity"]
            = $quantity;

    }

}


/**
 * Remove product from cart.
 */
function removeFromCart(
    string $cartKey
): void {

    if (
        isset(
            $_SESSION["cart"][
                $cartKey
            ]
        )
    ) {

        unset(
            $_SESSION["cart"][
                $cartKey
            ]
        );

    }

}


/**
 * Clear entire cart.
 */
function clearCart(): void
{

    $_SESSION["cart"] = [];

}


/**
 * Get total number of products in cart.
 */
function getCartItemCount(): int
{

    if (
        !isset(
            $_SESSION["cart"]
        )
        ||
        empty(
            $_SESSION["cart"]
        )
    ) {

        return 0;

    }


    $count = 0;


    foreach (
        $_SESSION["cart"]
        as $cartItem
    ) {

        if (
            isset(
                $cartItem["quantity"]
            )
        ) {

            $count +=
                (int)
                $cartItem[
                    "quantity"
                ];

        }

    }


    return $count;

}


/**
 * Get number of unique items in cart.
 */
function getUniqueCartItemCount(): int
{

    if (
        !isset(
            $_SESSION["cart"]
        )
        ||
        empty(
            $_SESSION["cart"]
        )
    ) {

        return 0;

    }


    return count(
        $_SESSION["cart"]
    );

}


/*
|--------------------------------------------------------------------------
| ADDRESS FUNCTIONS
|--------------------------------------------------------------------------
*/


/**
 * Get all addresses belonging to a user.
 */
function getUserAddresses(
    PDO $pdo,
    int $userId
): array {

    $stmt =
        $pdo->prepare(

            "SELECT

                id,
                user_id,
                full_name,
                phone,
                address_line,
                city,
                state,
                pincode,
                country,
                is_default,
                created_at

             FROM addresses

             WHERE user_id = ?

             ORDER BY

                is_default DESC,

                created_at DESC"

        );


    $stmt->execute([

        $userId

    ]);


    return $stmt->fetchAll();

}


/**
 * Get one address belonging to a user.
 */
function getUserAddress(
    PDO $pdo,
    int $addressId,
    int $userId
): ?array {

    $stmt =
        $pdo->prepare(

            "SELECT

                id,
                user_id,
                full_name,
                phone,
                address_line,
                city,
                state,
                pincode,
                country,
                is_default

             FROM addresses

             WHERE id = ?

             AND user_id = ?

             LIMIT 1"

        );


    $stmt->execute([

        $addressId,

        $userId

    ]);


    $address =
        $stmt->fetch();


    return $address
        ?: null;

}


/*
|--------------------------------------------------------------------------
| CART PRODUCT FUNCTIONS
|--------------------------------------------------------------------------
*/


/**
 * Get cart products with vendor information.
 *
 * This function supports the new cart structure.
 */
function getCartProducts(
    PDO $pdo,
    array $cart
): array {

    if (
        empty($cart)
    ) {

        return [];

    }


    /*
    |--------------------------------------------------------------------------
    | Extract product IDs
    |--------------------------------------------------------------------------
    */

    $productIds = [];


    foreach (
        $cart
        as $cartItem
    ) {

        if (
            isset(
                $cartItem[
                    "product_id"
                ]
            )
        ) {

            $productIds[] =
                (int)
                $cartItem[
                    "product_id"
                ];

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Remove duplicate IDs
    |--------------------------------------------------------------------------
    */

    $productIds =
        array_values(
            array_unique(
                $productIds
            )
        );


    if (
        empty(
            $productIds
        )
    ) {

        return [];

    }


    /*
    |--------------------------------------------------------------------------
    | Create SQL placeholders
    |--------------------------------------------------------------------------
    */

    $placeholders =
        implode(
            ",",
            array_fill(
                0,
                count(
                    $productIds
                ),
                "?"
            )
        );


    /*
    |--------------------------------------------------------------------------
    | Fetch products
    |--------------------------------------------------------------------------
    */

    $stmt =
        $pdo->prepare(

            "SELECT

                p.id,
                p.vendor_id,
                p.name,
                p.price,
                p.stock,
                p.image,
                p.status,

                v.store_name AS vendor_name

             FROM products p

             INNER JOIN vendors v

                ON v.id = p.vendor_id

             WHERE p.id IN (

                $placeholders

             )

             AND p.status = 'active'

             AND v.status = 'approved'"

        );


    $stmt->execute(

        $productIds

    );


    $products =
        $stmt->fetchAll();


    /*
    |--------------------------------------------------------------------------
    | Add cart quantities
    |--------------------------------------------------------------------------
    */

    foreach (
        $products
        as &$product
    ) {

        $productId =
            (int)
            $product["id"];


        $totalQuantity = 0;


        foreach (
            $cart
            as $cartItem
        ) {

            if (
                isset(
                    $cartItem[
                        "product_id"
                    ]
                )

                &&

                (int)
                $cartItem[
                    "product_id"
                ]

                ===

                $productId
            ) {

                $totalQuantity +=

                    (int)
                    (
                        $cartItem[
                            "quantity"
                        ]
                        ?? 0
                    );

            }

        }


        $product["quantity"] =
            $totalQuantity;

    }


    unset(
        $product
    );


    return $products;

}


/*
|--------------------------------------------------------------------------
| CUSTOMER ORDER FUNCTIONS
|--------------------------------------------------------------------------
*/


/**
 * Get all orders belonging to a customer.
 */
function getUserOrders(
    PDO $pdo,
    int $userId
): array {

    $stmt =
        $pdo->prepare(

            "SELECT

                o.id,
                o.total_amount,
                o.discount_amount,
                o.shipping_amount,
                o.payment_method,
                o.payment_status,
                o.order_status,
                o.created_at,

                a.full_name,
                a.city,
                a.state,
                a.pincode

             FROM orders o

             INNER JOIN addresses a

                ON a.id = o.address_id

             WHERE o.user_id = ?

             ORDER BY

                o.created_at DESC"

        );


    $stmt->execute([

        $userId

    ]);


    return $stmt->fetchAll();

}


/**
 * Get one order belonging to a specific customer.
 */
function getUserOrder(
    PDO $pdo,
    int $orderId,
    int $userId
): ?array {

    $stmt =
        $pdo->prepare(

            "SELECT

                o.id,
                o.user_id,
                o.address_id,
                o.total_amount,
                o.discount_amount,
                o.shipping_amount,
                o.payment_method,
                o.payment_status,
                o.order_status,
                o.created_at,

                a.full_name,
                a.phone,
                a.address_line,
                a.city,
                a.state,
                a.pincode,
                a.country

             FROM orders o

             INNER JOIN addresses a

                ON a.id = o.address_id

             WHERE o.id = ?

             AND o.user_id = ?

             LIMIT 1"

        );


    $stmt->execute([

        $orderId,

        $userId

    ]);


    $order =
        $stmt->fetch();


    return $order
        ?: null;

}


/**
 * Get all items belonging to an order.
 *
 * Includes item_status for multi-vendor
 * order management.
 */
function getOrderItems(
    PDO $pdo,
    int $orderId
): array {

    $stmt =
        $pdo->prepare(

            "SELECT

                oi.id,
                oi.order_id,
                oi.product_id,
                oi.vendor_id,
                oi.variation_id,
                oi.quantity,
                oi.price,
                oi.item_status,

                p.name AS product_name,
                p.image AS product_image,

                v.store_name AS vendor_name

             FROM order_items oi

             INNER JOIN products p

                ON p.id = oi.product_id

             INNER JOIN vendors v

                ON v.id = oi.vendor_id

             WHERE oi.order_id = ?

             ORDER BY

                oi.id ASC"

        );


    $stmt->execute([

        $orderId

    ]);


    return $stmt->fetchAll();

}


/*
|--------------------------------------------------------------------------
| VENDOR ORDER FUNCTIONS
|--------------------------------------------------------------------------
*/


/**
 * Get order items belonging to a specific vendor.
 */
function getVendorOrderItems(
    PDO $pdo,
    int $orderId,
    int $vendorId
): array {

    $stmt =
        $pdo->prepare(

            "SELECT

                oi.id,
                oi.order_id,
                oi.product_id,
                oi.vendor_id,
                oi.variation_id,
                oi.quantity,
                oi.price,
                oi.item_status,

                p.name AS product_name,
                p.image AS product_image

             FROM order_items oi

             INNER JOIN products p

                ON p.id = oi.product_id

             WHERE oi.order_id = ?

             AND oi.vendor_id = ?

             ORDER BY

                oi.id ASC"

        );


    $stmt->execute([

        $orderId,

        $vendorId

    ]);


    return $stmt->fetchAll();

}


/**
 * Get vendor's orders.
 */
function getVendorOrders(
    PDO $pdo,
    int $vendorId
): array {

    $stmt =
        $pdo->prepare(

            "SELECT

                o.id AS order_id,
                o.user_id,
                o.address_id,
                o.total_amount,
                o.discount_amount,
                o.shipping_amount,
                o.payment_method,
                o.payment_status,
                o.order_status,
                o.created_at,

                u.name AS customer_name,
                u.email AS customer_email,

                COUNT(
                    oi.id
                ) AS item_count

             FROM orders o

             INNER JOIN order_items oi

                ON oi.order_id = o.id

             INNER JOIN users u

                ON u.id = o.user_id

             WHERE oi.vendor_id = ?

             GROUP BY

                o.id,
                o.user_id,
                o.address_id,
                o.total_amount,
                o.discount_amount,
                o.shipping_amount,
                o.payment_method,
                o.payment_status,
                o.order_status,
                o.created_at,
                u.name,
                u.email

             ORDER BY

                o.created_at DESC"

        );


    $stmt->execute([

        $vendorId

    ]);


    return $stmt->fetchAll();

}


/*
|--------------------------------------------------------------------------
| WISHLIST FUNCTIONS
|--------------------------------------------------------------------------
*/


/**
 * Add product to wishlist.
 */
function addToWishlist(
    PDO $pdo,
    int $userId,
    int $productId
): bool {

    $stmt =
        $pdo->prepare(

            "INSERT IGNORE INTO wishlist

            (

                user_id,

                product_id

            )

            VALUES

            (

                ?,

                ?

            )"

        );


    return $stmt->execute([

        $userId,

        $productId

    ]);

}


/**
 * Remove product from wishlist.
 */
function removeFromWishlist(
    PDO $pdo,
    int $userId,
    int $productId
): bool {

    $stmt =
        $pdo->prepare(

            "DELETE FROM wishlist

             WHERE user_id = ?

             AND product_id = ?"

        );


    return $stmt->execute([

        $userId,

        $productId

    ]);

}


/**
 * Check if product is in wishlist.
 */
function isInWishlist(
    PDO $pdo,
    int $userId,
    int $productId
): bool {

    $stmt =
        $pdo->prepare(

            "SELECT

                id

             FROM wishlist

             WHERE user_id = ?

             AND product_id = ?

             LIMIT 1"

        );


    $stmt->execute([

        $userId,

        $productId

    ]);


    return (bool)
        $stmt->fetch();

}


/**
 * Get all wishlist products for a user.
 */
function getUserWishlist(
    PDO $pdo,
    int $userId
): array {

    $stmt =
        $pdo->prepare(

            "SELECT

                w.id AS wishlist_id,

                p.id AS product_id,
                p.name,
                p.price,
                p.stock,
                p.image,
                p.status,

                v.id AS vendor_id,
                v.store_name AS vendor_name

             FROM wishlist w

             INNER JOIN products p

                ON p.id = w.product_id

             INNER JOIN vendors v

                ON v.id = p.vendor_id

             WHERE w.user_id = ?

             ORDER BY

                w.id DESC"

        );


    $stmt->execute([

        $userId

    ]);


    return $stmt->fetchAll();
/*
|--------------------------------------------------------------------------
| GET SITE SETTING
|--------------------------------------------------------------------------
*/

function getSetting($pdo, $key, $default = "")
{

    static $cache = null;

    if ($cache === null) {

        $cache = [];

        $stmt =
            $pdo->query(
                "SELECT setting_key, setting_value
                 FROM settings"
            );

        foreach (
            $stmt->fetchAll(PDO::FETCH_ASSOC)
            as $row
        ) {

            $cache[$row["setting_key"]] =
                $row["setting_value"];

        }

    }

    return $cache[$key] ?? $default;

}
}