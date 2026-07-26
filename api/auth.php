<?php

/*
|--------------------------------------------------------------------------
| POST /api/auth.php
|--------------------------------------------------------------------------
|
| Body (JSON or form): { "email": "...", "password": "..." }
|
| Returns a Bearer token to use on other API endpoints, e.g.:
|
| Authorization: Bearer <token>
|
*/

require_once __DIR__ . "/config.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    jsonError("Only POST is allowed on this endpoint.", 405);
}

$input = json_decode(file_get_contents("php://input"), true);

if (!is_array($input)) {
    $input = [];
}

$email = trim($input["email"] ?? $_POST["email"] ?? "");
$password = (string) ($input["password"] ?? $_POST["password"] ?? "");

if ($email === "" || $password === "") {
    jsonError("Email and password are required.");
}

$stmt = $pdo->prepare(
    "SELECT id, name, email, password, role, status
     FROM users
     WHERE email = ?"
);

$stmt->execute([$email]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user["password"])) {
    jsonError("Invalid email or password.", 401);
}

if ($user["status"] !== "active") {
    jsonError("This account is not active.", 403);
}

$token = generateApiToken($user["id"]);

jsonResponse([
    "success" => true,
    "token" => $token,
    "expires_in" => 604800,
    "user" => [
        "id" => (int) $user["id"],
        "name" => $user["name"],
        "email" => $user["email"],
        "role" => $user["role"],
    ],
]);