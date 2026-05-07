<?php
// Email sending helpers built on top of PHPMailer and the shared config.
require_once __DIR__ . '/config.php';

// Load PHPMailer classes directly from the vendor folder.
require_once __DIR__ . '/../vendor/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Send a plain-text email alert via SMTP using PHPMailer.
 * Returns true if sent; false if failed (errors logged in server logs).
 */
// Build and send a single SMTP email message.
function sendEmailAlert(string $toEmail, string $toName, string $subject, string $bodyText): bool
{
    if (!EMAIL_ENABLED) return true;

    // Create a PHPMailer instance that raises exceptions on transport errors.
    $mail = new PHPMailer(true);

    try {
        // Configure SMTP transport with credentials from config.php.
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        // Set sender and recipient details.
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        // Send a plain-text UTF-8 email body.
        $mail->isHTML(false);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body    = $bodyText;

        return $mail->send();
    } catch (Exception $e) {
        // Write the mailer failure to server logs for troubleshooting.
        error_log("Email send failed: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Convenience: send email only if user exists AND email notifications enabled.
 */
// Look up the target user and honor their notification preference before sending.
function sendEmailToUser(mysqli $conn, int $userId, string $subject, string $bodyText): bool
{
    $info = getUserInfo($conn, $userId);
    if (!$info) return false;

    if ((int)$info['email_notifications'] !== 1) {
        return true; // user opted out
    }

    return sendEmailAlert($info['email'], $info['name'], $subject, $bodyText);
}