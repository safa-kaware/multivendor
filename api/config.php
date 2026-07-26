<?php

/*
|--------------------------------------------------------------------------
| API BOOTSTRAP
|--------------------------------------------------------------------------
|
| Shared bootstrap for all REST API endpoints under /api.
| Loads the main app config (DB, helpers) and adds lightweight
| stateless Bearer-token authentication, so the API does not need
| a browser session/cookie to work (e.g. from a mobile app).
|
*/

require_once __DIR__ . "/../config/app.php";

header("Content-Type: application/json");

// Allow the API to be called from other origins (mobile apps, etc.)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}


/*
|--------------------------------------------------------------------------
| SECRET KEY
|--------------------------------------------------------------------------
|
| Used only to sign API tokens (HMAC). Change this before deploying
| to production.
|
*/

if (!defined("API_SECRET_KEY")) {
    define("API_SECRET_KEY", "multivendor_api_secret_key_change_me");
}


/*
|--------------------------------------------------------------------------
| JSON RESPONSE HELPER
|--------------------------------------------------------------------------
*/

function jsonResponse($data, $statusCode = 200)
{
    http_response_code($statusCode);

    echo json_encode(
        $data,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    );

    exit;
}


function jsonError($message, $statusCode = 400)
{
    jsonResponse(
        [
            "success" => false,
            "error" => $message,
        ],
        $statusCode
    );
}


/*
|--------------------------------------------------------------------------
| TOKEN GENERATION
|--------------------------------------------------------------------------
|
| Stateless signed token: base64( userId . "." . expiry . "." . signature )
| No database column or table needed to store tokens.
|
*/

function generateApiToken($userId, $ttlSeconds = 604800)
{
    $expiry = time() + $ttlSeconds;

    $payload = $userId . "." . $expiry;

    $signature = hash_hmac("sha256", $payload, API_SECRET_KEY);

    return base64_encode($payload . "." . $signature);
}


/*
|--------------------------------------------------------------------------
| TOKEN VALIDATION
|--------------------------------------------------------------------------
|
| Returns the authenticated user_id on success, or null on failure
| (invalid signature, malformed token, or expired).
|
*/

function validateApiToken($token)
{
    $decoded = base64_decode($token, true);

    if ($decoded === false) {
        return null;
    }

    $parts = explode(".", $decoded);

    if (count($parts) !== 3) {
        return null;
    }

    [$userId, $expiry, $signature] = $parts;

    $payload = $userId . "." . $expiry;

    $expectedSignature = hash_hmac("sha256", $payload, API_SECRET_KEY);

    if (!hash_equals($expectedSignature, $signature)) {
        return null;
    }

    if ((int) $expiry < time()) {
        return null;
    }

    return (int) $userId;
}


/*
|--------------------------------------------------------------------------
| REQUIRE AUTHENTICATED API REQUEST
|--------------------------------------------------------------------------
|
| Reads the Authorization: Bearer <token> header, validates it, and
| returns the authenticated user_id. Sends a 401 JSON error and exits
| if the token is missing or invalid.
|
*/

function requireApiAuth()
{
    $headers = [];

    if (function_exists("getallheaders")) {
        $headers = getallheaders();
    }

    $authHeader = "";

    foreach ($headers as $key => $value) {
        if (strtolower($key) === "authorization") {
            $authHeader = $value;
        }
    }

    if ($authHeader === "" && isset($_SERVER["HTTP_AUTHORIZATION"])) {
        $authHeader = $_SERVER["HTTP_AUTHORIZATION"];
    }

    if (!preg_match("/Bearer\s+(.*)$/i", $authHeader, $matches)) {
        jsonError("Missing or invalid Authorization header. Expected: Bearer <token>", 401);
    }

    $userId = validateApiToken(trim($matches[1]));

    if ($userId === null) {
        jsonError("Invalid or expired token.", 401);
    }

    return $userId;
}