<?php

require_once "../config/app.php";

requireAdmin();


/*
|--------------------------------------------------------------------------
| GET USER ID
|--------------------------------------------------------------------------
*/

$userId = isset($_GET["id"])
    ? (int) $_GET["id"]
    : 0;


/*
|--------------------------------------------------------------------------
| GET STATUS
|--------------------------------------------------------------------------
*/

$status = $_GET["status"] ?? "";


/*
|--------------------------------------------------------------------------
| VALIDATE USER ID
|--------------------------------------------------------------------------
*/

if ($userId <= 0) {

    http_response_code(400);

    die("Invalid user ID.");

}


/*
|--------------------------------------------------------------------------
| VALIDATE STATUS
|--------------------------------------------------------------------------
*/

$allowedStatuses = [

    "active",

    "inactive",

    "blocked"

];


if (
    !in_array(
        $status,
        $allowedStatuses,
        true
    )
) {

    http_response_code(400);

    die("Invalid user status.");

}


/*
|--------------------------------------------------------------------------
| PREVENT ADMIN FROM BLOCKING THEMSELVES
|--------------------------------------------------------------------------
*/

if (
    $userId
    ===
    (int)
    currentUserId()
) {

    die(
        "You cannot change your own account status."
    );

}


/*
|--------------------------------------------------------------------------
| CHECK USER
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(

    "SELECT
        id,
        role

     FROM users

     WHERE id = ?

     LIMIT 1"

);

$stmt->execute([

    $userId

]);

$user =
    $stmt->fetch();


if (!$user) {

    http_response_code(404);

    die(
        "User not found."
    );

}


/*
|--------------------------------------------------------------------------
| UPDATE USER STATUS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(

    "UPDATE users

     SET status = ?

     WHERE id = ?"

);

$stmt->execute([

    $status,

    $userId

]);


/*
|--------------------------------------------------------------------------
| IF VENDOR IS BLOCKED
|--------------------------------------------------------------------------
|
| Also suspend their vendor store.
|
*/

if (
    $user["role"]
    === "vendor"
) {

    if (
        $status
        === "blocked"
    ) {

        $stmt = $pdo->prepare(

            "UPDATE vendors

             SET status = 'suspended'

             WHERE user_id = ?"

        );

        $stmt->execute([

            $userId

        ]);

    }

}


/*
|--------------------------------------------------------------------------
| REDIRECT
|--------------------------------------------------------------------------
*/

redirect(

    BASE_URL
    . "admin/users.php"

);