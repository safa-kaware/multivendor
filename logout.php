<?php

require_once "config/app.php";


// Log out user
logoutUser();


// Start a new session for flash message
session_start();

$_SESSION["auth_success"] =
    "You have been logged out successfully.";


redirect(
    BASE_URL . "login.php"
);