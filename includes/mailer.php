<?php
// ============================================
// mailer.php — Email Sending Utility
// Place this file in: includes/mailer.php
// ============================================

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Send an email using PHPMailer and Google App Passwords
 *
 * @param string $toEmail The recipient's email address
 * @param string $toName The recipient's name (optional)
 * @param string $subject The email subject
 * @param string $bodyHtml The HTML body of the email
 * @param string $bodyText The plain text alternative body (optional)
 * @return bool True on success, false on failure
 */
function sendEmail(string $toEmail, string $toName, string $subject, string $bodyHtml, string $bodyText = ''): bool {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption (for port 587)
        $mail->Port       = SMTP_PORT;

        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $bodyHtml;
        $mail->AltBody = empty($bodyText) ? strip_tags($bodyHtml) : $bodyText;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
