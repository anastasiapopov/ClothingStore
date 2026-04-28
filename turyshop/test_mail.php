<?php
require __DIR__ . "/includes/mailer.php";

sendMail(
    "anastasia.popov.2023@gmail.com",
    "Test TuryShop",
    "<b>Merge SMTP!</b>"
);

echo "Trimis!";