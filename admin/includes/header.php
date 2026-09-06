<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>

        <?= e(
            $pageTitle
            ?? APP_NAME
        ) ?>

    </title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

</head>


<body>


<nav class="navbar navbar-dark bg-dark">


    <div class="container-fluid">


        <a
            class="navbar-brand fw-bold"
            href="<?= BASE_URL ?>admin/dashboard.php"
        >

            <?= e(
                APP_NAME
            ) ?>

            Admin

        </a>


        <div>


            <span class="text-white me-3">

                <?= e(
                    $_SESSION["user_name"]
                ) ?>

            </span>


            <a
                href="<?= BASE_URL ?>logout.php"
                class="btn btn-outline-light btn-sm"
            >

                Logout

            </a>


        </div>


    </div>

</nav>


<div class="container-fluid">

<div class="row">


<!-- Sidebar -->


<aside
    class="col-md-3 col-lg-2 bg-light min-vh-100 p-3"
>


    <ul class="nav flex-column">


        <li class="nav-item mb-2">

            <a
                href="<?= BASE_URL ?>admin/dashboard.php"
                class="nav-link"
            >

                <i class="bi bi-speedometer2"></i>

                Dashboard

            </a>

        </li>


        <li class="nav-item mb-2">

            <a
                href="<?= BASE_URL ?>admin/products.php"
                class="nav-link"
            >

                <i class="bi bi-box"></i>

                Products

            </a>

        </li>


        <li class="nav-item mb-2">

            <a
                href="<?= BASE_URL ?>admin/categories.php"
                class="nav-link"
            >

                <i class="bi bi-grid"></i>

                Categories

            </a>

        </li>


        <li class="nav-item mb-2">

            <a
                href="<?= BASE_URL ?>admin/banners.php"
                class="nav-link"
            >

                <i class="bi bi-images"></i>

                Banners

            </a>

        </li>


        <li class="nav-item mb-2">

            <a
                href="<?= BASE_URL ?>admin/vendors.php"
                class="nav-link"
            >

                <i class="bi bi-shop"></i>

                Vendors

            </a>

        </li>


        <li class="nav-item mb-2">

            <a
                href="<?= BASE_URL ?>admin/orders.php"
                class="nav-link"
            >

                <i class="bi bi-cart"></i>

                Orders

            </a>

        </li>


        <li class="nav-item mb-2">

            <a
                href="<?= BASE_URL ?>admin/coupons.php"
                class="nav-link"
            >

                <i class="bi bi-ticket"></i>

                Coupons

            </a>

        </li>


        <li class="nav-item mb-2">

            <a
                href="<?= BASE_URL ?>admin/reports.php"
                class="nav-link"
            >

                <i class="bi bi-bar-chart"></i>

                Reports

            </a>

        </li>


        <li class="nav-item mb-2">

            <a
                href="<?= BASE_URL ?>admin/settings.php"
                class="nav-link"
            >

                <i class="bi bi-gear"></i>

                Settings

            </a>

        </li>


    </ul>


</aside>


<!-- Main Content -->


<div class="col-md-9 col-lg-10 p-4">