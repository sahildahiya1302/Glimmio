<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/../../vendor/autoload.php';

function send_mail(string $to, string $subject, string $body): bool {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'home@glimmio.com';
        $mail->Password = 'hthv ivpx qaiq bstk';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom('noreply@glimmio.com', 'Glimmio Website');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->send();
        file_put_contents(__DIR__.'/../logs/mail.log', date('c')." sent to $to\n", FILE_APPEND);
        return true;
    } catch (Exception $e) {
        error_log('Mail error: '.$e->getMessage());
        return false;
    }
}

function send_otp_email(string $email, string $otp): bool {
    $body = '<p>Your verification code is <strong>' . htmlspecialchars($otp) . '</strong></p>';
    return send_mail($email, 'Glimmio Verification Code', $body);
}
?>
