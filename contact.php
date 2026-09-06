<?php

require_once "config/app.php";


$errors = [];

$success = "";

$name = "";

$email = "";

$message = "";


/*
|--------------------------------------------------------------------------
| HANDLE CONTACT FORM SUBMISSION
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"]
    === "POST"
) {

    $name =
        trim($_POST["name"] ?? "");

    $email =
        trim($_POST["email"] ?? "");

    $message =
        trim($_POST["message"] ?? "");


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($name === "") {

        $errors[] =
            "Please enter your name.";

    }

    if (
        $email === ""
        || !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {

        $errors[] =
            "Please enter a valid email address.";

    }

    if ($message === "") {

        $errors[] =
            "Please enter a message.";

    } elseif (strlen($message) < 10) {

        $errors[] =
            "Your message is too short. Please provide more detail.";

    }


    /*
    |--------------------------------------------------------------------------
    | SAVE TO DATABASE
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        $stmt =
            $pdo->prepare(
                "INSERT INTO feedback
                    (name, email, message, status)
                 VALUES (?, ?, ?, 'new')"
            );

        $stmt->execute([
            $name,
            $email,
            $message,
        ]);


        /*
        |--------------------------------------------------------------------------
        | SEND EMAIL NOTIFICATION
        |--------------------------------------------------------------------------
        |
        | This uses PHP's built-in mail() function. On XAMPP/Windows, this
        | requires sendmail.ini (or an SMTP relay) to be configured before
        | it will actually deliver — see config/mail_setup_instructions.txt.
        | The form still saves to the database and succeeds for the user
        | even if the email fails to send, so a missing mail config never
        | breaks the contact form itself.
        |
        */

        $adminEmail =
            function_exists("getSetting")
                ? getSetting($pdo, "site_email", "")
                : "";

        if ($adminEmail === "") {

            $adminEmail = "admin@" . parse_url(BASE_URL, PHP_URL_HOST);

        }

        $emailSubject =
            "New Contact Form Message - "
            . APP_NAME;

        $emailBody =
            "You have received a new message via the contact form.\r\n\r\n"
            . "Name: " . $name . "\r\n"
            . "Email: " . $email . "\r\n\r\n"
            . "Message:\r\n"
            . $message . "\r\n";

        $emailHeaders =
            "From: " . APP_NAME . " <no-reply@" . parse_url(BASE_URL, PHP_URL_HOST) . ">\r\n"
            . "Reply-To: " . $email . "\r\n"
            . "X-Mailer: PHP/" . phpversion();

        // Suppress warnings so a mail server issue never surfaces to the customer.
        @mail($adminEmail, $emailSubject, $emailBody, $emailHeaders);


        $success =
            "Thank you! Your message has been received. We'll get back to you soon.";

        $name = "";
        $email = "";
        $message = "";

    }

}


$pageTitle =
    "Contact Us | "
    . APP_NAME;


require_once "includes/header.php";

?>


<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-lg-7">

            <h1 class="fw-bold mb-2">
                Contact Us
            </h1>

            <p class="text-muted mb-4">
                Have a question or feedback? Send us a message and we'll respond as soon as possible.
            </p>


            <?php if ($success !== ""): ?>

                <div class="alert alert-success">
                    <?= e($success) ?>
                </div>

            <?php endif; ?>


            <?php if (!empty($errors)): ?>

                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $err): ?>
                            <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

            <?php endif; ?>


            <div class="card border-0 shadow-sm">

                <div class="card-body">

                    <form method="POST">

                        <div class="mb-3">
                            <label class="form-label">Your Name</label>
                            <input
                                type="text"
                                name="name"
                                class="form-control"
                                value="<?= e($name) ?>"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Your Email</label>
                            <input
                                type="email"
                                name="email"
                                class="form-control"
                                value="<?= e($email) ?>"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Message</label>
                            <textarea
                                name="message"
                                class="form-control"
                                rows="5"
                                required
                            ><?= e($message) ?></textarea>
                        </div>

                        <button
                            type="submit"
                            class="btn btn-dark"
                        >
                            Send Message
                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>


<?php

require_once "includes/footer.php";