<?php
// Include the Composer autoloader
require 'vendor/autoload.php';  // Path to the Composer autoload file

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$phpmailer = new PHPMailer();

try {
    // Server settings
    $phpmailer->isSMTP();
    $phpmailer->Host = 'live.smtp.mailtrap.io';
    $phpmailer->SMTPAuth = true;
    $phpmailer->Port = 587;
    $phpmailer->Username = 'api';
    $phpmailer->Password = '447b12612cf5611bb28968d294af00da';
    $phpmailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

    // Sender and recipient
    $phpmailer->setFrom('no-reply@demomailtrap.com', 'SVMIS');
    $phpmailer->addAddress('svmis.official@gmail.com');

    // Content
    $phpmailer->isHTML(true);
    $phpmailer->Subject = 'Test Email from PHPMailer';
    $phpmailer->Body = 'This is a test email sent via PHPMailer using Mailtrap.';

    // Send the email
    if ($phpmailer->send()) {
        echo 'Message has been sent successfully.';
    } else {
        echo 'Message could not be sent. Mailer Error: ' . $phpmailer->ErrorInfo;
    }
} catch (Exception $e) {
    echo 'Message could not be sent. PHPMailer Error: ' . $phpmailer->ErrorInfo;
}
?>
