<?php

/*
|--------------------------------------------------------------------------
| USER REGISTRATION
|--------------------------------------------------------------------------
*/


/**
 * Register a new customer.
 */
function registerUser(
    $pdo,
    $name,
    $email,
    $password
) {
    // Check if email already exists
    $stmt = $pdo->prepare(
        "SELECT id
         FROM users
         WHERE email = ?
         LIMIT 1"
    );

    $stmt->execute([
        $email
    ]);

    if ($stmt->fetch()) {

        return [
            "success" => false,
            "message" => "An account with this email already exists."
        ];

    }


    // Hash password securely
    $hashedPassword = password_hash(
        $password,
        PASSWORD_DEFAULT
    );


    // Insert new customer
    $stmt = $pdo->prepare(
        "INSERT INTO users
        (
            name,
            email,
            password,
            role,
            status
        )
        VALUES
        (
            ?,
            ?,
            ?,
            'customer',
            'active'
        )"
    );

    $stmt->execute([
        $name,
        $email,
        $hashedPassword
    ]);


    return [
        "success" => true,
        "message" => "Account created successfully."
    ];
}


/*
|--------------------------------------------------------------------------
| USER LOGIN
|--------------------------------------------------------------------------
*/


/**
 * Authenticate user.
 */
function loginUser(
    $pdo,
    $email,
    $password
) {

    // Find user by email
    $stmt = $pdo->prepare(
        "SELECT
            id,
            name,
            email,
            password,
            role,
            status

         FROM users

         WHERE email = ?

         LIMIT 1"
    );

    $stmt->execute([
        $email
    ]);

    $user =
        $stmt->fetch();


    // Check if user exists
    if (!$user) {

        return [
            "success" => false,
            "message" => "Invalid email or password."
        ];

    }


    // Verify password
    if (
        !password_verify(
            $password,
            $user["password"]
        )
    ) {

        return [
            "success" => false,
            "message" => "Invalid email or password."
        ];

    }


    // Check account status
    if (
        $user["status"]
        !== "active"
    ) {

        return [
            "success" => false,
            "message" => "Your account is not active."
        ];

    }


    // Regenerate session ID
    // after successful login
    session_regenerate_id(true);


    // Store user information
    // in session
    $_SESSION["user_id"] =
        $user["id"];

    $_SESSION["user_name"] =
        $user["name"];

    $_SESSION["user_email"] =
        $user["email"];

    $_SESSION["user_role"] =
        $user["role"];


    return [
        "success" => true,
        "message" => "Login successful.",
        "role" => $user["role"]
    ];
}


/*
|--------------------------------------------------------------------------
| USER LOGOUT
|--------------------------------------------------------------------------
*/


/**
 * Log out current user.
 */
function logoutUser()
{

    // Unset all session variables
    $_SESSION = [];


    // Delete session cookie
    if (
        ini_get(
            "session.use_cookies"
        )
    ) {

        $params =
            session_get_cookie_params();


        setcookie(
            session_name(),
            "",
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );

    }


    // Destroy session
    session_destroy();

}


/*
|--------------------------------------------------------------------------
| GENERAL AUTHENTICATION
|--------------------------------------------------------------------------
*/


/**
 * Require user to be logged in.
 */
function requireLogin()
{

    if (
        !isLoggedIn()
    ) {

        redirect(
            BASE_URL
            . "login.php"
        );

    }

}


/**
 * Require a specific user role.
 */
function requireRole(
    $role
) {

    // User must be logged in
    if (
        !isLoggedIn()
    ) {

        redirect(
            BASE_URL
            . "login.php"
        );

    }


    // Check role
    if (
        currentUserRole()
        !== $role
    ) {

        http_response_code(
            403
        );

        die(
            "Access denied."
        );

    }

}


/*
|--------------------------------------------------------------------------
| CUSTOMER AUTHENTICATION
|--------------------------------------------------------------------------
*/


/**
 * Require customer account.
 */
function requireCustomer()
{

    requireRole(
        "customer"
    );

}


/*
|--------------------------------------------------------------------------
| ADMIN AUTHENTICATION
|--------------------------------------------------------------------------
*/


/**
 * Check if current user is an admin.
 */
function isAdmin(): bool
{

    return (

        isset(
            $_SESSION["user_id"]
        )

        &&

        isset(
            $_SESSION["user_role"]
        )

        &&

        $_SESSION["user_role"]
        === "admin"

    );

}


/**
 * Require admin authentication.
 */
function requireAdmin(): void
{

    if (
        !isAdmin()
    ) {

        redirect(
            BASE_URL
            . "admin/index.php"
        );

    }

}


/*
|--------------------------------------------------------------------------
| VENDOR AUTHENTICATION
|--------------------------------------------------------------------------
*/


/**
 * Check if current user is an approved vendor.
 */
function isApprovedVendor($pdo): bool
{
    // Make sure user is logged in
    if (!isset($_SESSION["user_id"])) {
        return false;
    }

    // Make sure logged-in user has vendor role
    if (
        !isset($_SESSION["user_role"])
        || $_SESSION["user_role"] !== "vendor"
    ) {
        return false;
    }

    // Find approved vendor belonging to logged-in user
    $stmt = $pdo->prepare(
        "SELECT id
         FROM vendors
         WHERE user_id = ?
         AND status = 'approved'
         LIMIT 1"
    );

    $stmt->execute([
        (int) $_SESSION["user_id"]
    ]);

    $vendor = $stmt->fetch();

    // Return true if approved vendor exists
    return $vendor !== false;
}


/**
 * Require approved vendor access.
 */
function requireVendor($pdo): void
{
    if (!isApprovedVendor($pdo)) {

        http_response_code(403);

        die(
            "Access denied. Your vendor account is not approved."
        );
    }
}


/**
 * Get current vendor ID.
 */
function currentVendorId($pdo): ?int
{
    if (!isset($_SESSION["user_id"])) {
        return null;
    }

    $stmt = $pdo->prepare(
        "SELECT id
         FROM vendors
         WHERE user_id = ?
         AND status = 'approved'
         LIMIT 1"
    );

    $stmt->execute([
        (int) $_SESSION["user_id"]
    ]);

    $vendor = $stmt->fetch();

    if (!$vendor) {
        return null;
    }

    return (int) $vendor["id"];
}