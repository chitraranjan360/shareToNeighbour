<?php
require_once __DIR__ . '/config.php';

// Manual PHPMailer includes
require_once __DIR__ . '/../vendor/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Send a plain-text email alert via SMTP using PHPMailer.
 * Returns true if sent; false if failed (errors logged in server logs).
 */
function sendEmailAlert(string $toEmail, string $toName, string $subject, string $bodyText): bool
{
    if (!EMAIL_ENABLED) return true;

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(false);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body    = $bodyText;

        return $mail->send();
    } catch (Exception $e) {
        error_log("Email send failed: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Convenience: send email only if user exists AND email notifications enabled.
 */
function sendEmailToUser(mysqli $conn, int $userId, string $subject, string $bodyText): bool
{
    $info = getUserInfo($conn, $userId);
    if (!$info) return false;

    if ((int)$info['email_notifications'] !== 1) {
        return true; // user opted out
    }

    return sendEmailAlert($info['email'], $info['name'], $subject, $bodyText);
}