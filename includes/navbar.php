<nav
    class="navbar navbar-expand-lg navbar-dark bg-dark"
>

    <div class="container">


        <!-- BRAND -->

        <a
            class="navbar-brand fw-bold"
            href="<?= BASE_URL ?>"
        >

            <i class="bi bi-shop"></i>

            <?= e(APP_NAME) ?>

        </a>



        <!-- MOBILE BUTTON -->

        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#mainNavbar"
        >

            <span
                class="navbar-toggler-icon"
            ></span>

        </button>



        <!-- NAVIGATION -->

        <div
            class="collapse navbar-collapse"
            id="mainNavbar"
        >


            <!-- LEFT -->

            <ul
                class="navbar-nav me-auto mb-2 mb-lg-0"
            >


                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="<?= BASE_URL ?>"
                    >

                        <i class="bi bi-house"></i>

                        Home

                    </a>

                </li>



                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="<?= BASE_URL ?>search.php"
                    >

                        <i class="bi bi-grid"></i>

                        Products

                    </a>

                </li>



                <?php if (
                    isLoggedIn()
                    &&
                    currentUserRole() === "customer"
                ): ?>


                    <!-- WISHLIST -->

                    <li class="nav-item">

                        <a
                            class="nav-link"
                            href="<?= BASE_URL ?>wishlist.php"
                        >

                            <i class="bi bi-heart"></i>

                            Wishlist

                        </a>

                    </li>



                    <!-- MY ORDERS -->

                    <li class="nav-item">

                        <a
                            class="nav-link"
                            href="<?= BASE_URL ?>my-orders.php"
                        >

                            <i class="bi bi-box-seam"></i>

                            My Orders

                        </a>

                    </li>


                <?php endif; ?>


            </ul>



            <!-- RIGHT -->

            <ul
                class="navbar-nav ms-auto"
            >


                <!-- CART -->

                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="<?= BASE_URL ?>cart.php"
                    >

                        <i class="bi bi-cart3"></i>

                        Cart

                    </a>

                </li>



                <?php if (
                    isLoggedIn()
                ): ?>


                    <!-- USER -->

                    <li class="nav-item dropdown">

                        <a
                            class="nav-link dropdown-toggle"
                            href="#"
                            role="button"
                            data-bs-toggle="dropdown"
                        >

                            <i class="bi bi-person-circle"></i>

                            <?= e(
                                $_SESSION["user_name"]
                                ?? "Account"
                            ) ?>

                        </a>


                        <ul
                            class="dropdown-menu dropdown-menu-end"
                        >

                            <?php if (
                                currentUserRole()
                                === "vendor"
                            ): ?>

                                <li>

                                    <a
                                        class="dropdown-item"
                                        href="<?= BASE_URL ?>vendor/dashboard.php"
                                    >

                                        Vendor Dashboard

                                    </a>

                                </li>

                            <?php endif; ?>


                            <?php if (
                                currentUserRole()
                                === "admin"
                            ): ?>

                                <li>

                                    <a
                                        class="dropdown-item"
                                        href="<?= BASE_URL ?>admin/dashboard.php"
                                    >

                                        Admin Dashboard

                                    </a>

                                </li>

                            <?php endif; ?>


                            <?php if (
                                currentUserRole()
                                === "customer"
                            ): ?>

                                <li>

                                    <a
                                        class="dropdown-item"
                                        href="<?= BASE_URL ?>my-orders.php"
                                    >

                                        My Orders

                                    </a>

                                </li>

                            <?php endif; ?>


                            <li>

                                <hr
                                    class="dropdown-divider"
                                >

                            </li>


                            <li>

                                <a
                                    class="dropdown-item text-danger"
                                    href="<?= BASE_URL ?>logout.php"
                                >

                                    Logout

                                </a>

                            </li>


                        </ul>

                    </li>


                <?php else: ?>


                    <!-- LOGIN -->

                    <li class="nav-item">

                        <a
                            class="nav-link"
                            href="<?= BASE_URL ?>login.php"
                        >

                            Login

                        </a>

                    </li>



                    <!-- REGISTER -->

                    <li class="nav-item">

                        <a
                            class="btn btn-light btn-sm mt-1 ms-2"
                            href="<?= BASE_URL ?>register.php"
                        >

                            Register

                        </a>

                    </li>


                <?php endif; ?>


            </ul>


        </div>


    </div>

</nav>