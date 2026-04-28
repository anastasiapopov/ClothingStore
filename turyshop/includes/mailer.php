<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

function sendMail($to, $subject, $body) {

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        $mail->Username = 'anastasia.popov.2023@gmail.com';
        $mail->Password = 'hzgb bcqw gpyh aqbi'; // IMPORTANT

        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('anastasia.popov.2023@gmail.com', 'TuryShop');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();

    } catch (Exception $e) {
        // poți loga eroarea dacă vrei
    }
}