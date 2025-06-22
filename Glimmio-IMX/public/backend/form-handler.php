<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

$recipients = ['sahilpatel@glimmio.com', 'sahildahiya@glimmio.com', 'saket.mahar@glimmio.com'];

$mail = new PHPMailer(true);

try {
    // Configure mail server
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'home@glimmio.com';
    $mail->Password = 'hthv ivpx qaiq bstk';  // Gmail App Password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->setFrom('noreply@glimmio.com', 'Glimmio Website');

    foreach ($recipients as $recipient) {
        $mail->addAddress($recipient);
    }

    // Determine form type
    if (isset($_POST['form2-name'])) {
        handleServiceForm($mail);
    } elseif (isset($_POST['name']) && isset($_FILES['cv'])) {
        handleCvForm($mail);
    } else {
        throw new Exception("Invalid form submission");
    }

    $mail->send();

    // Success log
    file_put_contents(__DIR__ . '/../logs/mail.log', date('c') . " sent (PHPMailer)\n", FILE_APPEND);
    header('Location: ../pages/thank-you.html');
    exit;

} catch (Exception $e) {
    file_put_contents(__DIR__ . '/../logs/mail.log', date('c') . " failed: {$mail->ErrorInfo}\n", FILE_APPEND);
    error_log("Mailer Error: {$mail->ErrorInfo}");
    echo "Message could not be sent. Please try again later.";
}

// ----------- FUNCTIONS -----------

function handleServiceForm($mail) {
    $name = $_POST['form2-name'] ?? '';
    $email = $_POST['form2-email'] ?? '';
    $phone = $_POST['form2-phone'] ?? '';
    $services = $_POST['form2-services'] ?? ($_POST['services'] ?? '');
    $message = $_POST['form2-message'] ?? '';

    $mail->Subject = 'New Service Inquiry Submission';
    $mail->Body = "Name: $name\nEmail: $email\nPhone: $phone\nServices: $services\nMessage: $message";

    if (!empty($_FILES['cv']['tmp_name']) && $_FILES['cv']['error'] === UPLOAD_ERR_OK) {
        $mail->addAttachment($_FILES['cv']['tmp_name'], $_FILES['cv']['name']);
    }
}

function handleCvForm($mail) {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';

    $mail->Subject = 'New CV Application';
    $mail->Body = "New job application received:\n\nName: $name\nEmail: $email";

    if (!empty($_FILES['cv']['tmp_name']) && $_FILES['cv']['error'] === UPLOAD_ERR_OK) {
        $mail->addAttachment($_FILES['cv']['tmp_name'], $_FILES['cv']['name']);
    } else {
        throw new Exception("CV file upload failed.");
    }
}
