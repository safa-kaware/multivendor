<?php

$pageTitle =
    $pageTitle
    ?? APP_NAME;


$metaDescription =
    $metaDescription
    ?? "MultiVendor - Your trusted multi-vendor marketplace";

?>

<!DOCTYPE html>

<html lang="en">

<head>


    <meta charset="UTF-8">


    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >


    <meta
        name="description"
        content="<?= e($metaDescription) ?>"
    >


    <title>

        <?= e($pageTitle) ?>

    </title>



    <!-- BOOTSTRAP -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >



    <!-- BOOTSTRAP ICONS -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
    >



    <!-- CUSTOM CSS -->

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>assets/css/style.css"
    >

</head>


<body>


<?php

require_once __DIR__ . "/navbar.php";

?>