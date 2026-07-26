<?php

require_once __DIR__ . "/config/app.php";

echo "<h2>Session Test</h2>";

echo "<pre>";

echo "Session ID: ";
echo session_id();

echo "\n\n";

echo "Session Data:\n";

print_r($_SESSION);

echo "</pre>";