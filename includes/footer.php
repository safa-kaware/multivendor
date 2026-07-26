<footer class="bg-dark text-white mt-5">

    <div class="container py-5">

        <div class="row">


            <!-- About -->

            <div class="col-md-4 mb-4">

                <h5>
                    MultiVendor
                </h5>

                <p class="text-light">

                    Your trusted multi-vendor marketplace
                    for quality products from multiple sellers.

                </p>

            </div>


            <!-- Quick Links -->

            <div class="col-md-4 mb-4">

                <h5>
                    Quick Links
                </h5>

                <ul class="list-unstyled">

                    <li class="mb-2">

                        <a
                            href="<?= BASE_URL ?>"
                            class="text-white text-decoration-none"
                        >
                            Home
                        </a>

                    </li>


                    <li class="mb-2">

                        <a
                            href="<?= BASE_URL ?>search.php"
                            class="text-white text-decoration-none"
                        >
                            Products
                        </a>

                    </li>


                    <li class="mb-2">

                        <a
                            href="<?= BASE_URL ?>contact.php"
                            class="text-white text-decoration-none"
                        >
                            Contact
                        </a>

                    </li>

                </ul>

            </div>


            <!-- Social -->

            <div class="col-md-4 mb-4">

                <h5>
                    Follow Us
                </h5>


                <div>

                    <a
                        href="#"
                        class="text-white fs-4 me-3"
                    >

                        <i class="bi bi-facebook"></i>

                    </a>


                    <a
                        href="#"
                        class="text-white fs-4 me-3"
                    >

                        <i class="bi bi-instagram"></i>

                    </a>


                    <a
                        href="#"
                        class="text-white fs-4"
                    >

                        <i class="bi bi-twitter-x"></i>

                    </a>

                </div>

            </div>


        </div>

    </div>


    <div class="border-top border-secondary">

        <div class="container py-3 text-center">

            <small>

                &copy;
                <?= date("Y") ?>
                <?= e(APP_NAME) ?>.

                All Rights Reserved.

            </small>

        </div>

    </div>

</footer>


<!-- Bootstrap JavaScript -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
></script>


<!-- Custom JavaScript -->

<script
    src="<?= BASE_URL ?>assets/js/script.js"
></script>


</body>

</html>