<?php

require_once __DIR__ . "/app.php";


/**
 * Require user to be logged in.
 */
function requireLogin()
{
    if (!isLoggedIn()) {

        redirect(
            BASE_URL . "login.php"
        );
    }
}


/**
 * Require a specific user role.
 */
function requireRole($role)
{
    requireLogin();

    if (currentUserRole() !== $role) {

        http_response_code(403);

        die("Access Denied.");
    }
}