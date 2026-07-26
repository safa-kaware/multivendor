<?php

require_once __DIR__ . "/config/app.php";

$email = "vendor1@multivendor.local";
$newPassword = "Vendor@123";

$hashedPassword = password_hash(
    $newPassword,
    PASSWORD_DEFAULT
);

$stmt = $pdo->prepare(
    "UPDATE users
     SET password = ?
     WHERE email = ?"
);

$stmt->execute([
    $hashedPassword,
    $email
]);

echo "Password reset successfully.";