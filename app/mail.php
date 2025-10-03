<?php
require __DIR__ . '/../vendor/autoload.php'; 

use Dotenv\Dotenv;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load environment variables
try {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
} catch (Exception $e) {
    // Handle the exception if .env file is missing or cannot be loaded
    rfa_create_log("[OTP] Could not load .env file: " . $e->getMessage());
    return false;
}

/**
 * Send an email with a one-time password (OTP) to the recipient.
 *
 * @param string $email The recipient's email address.
 * @param string $name The recipient's name.
 * @param string $otp The one-time password (OTP) to be sent.
 * @param string $recipientName The recipient's name (optional, default: 'User').
 * @param int $otpValidity The validity of the OTP in minutes (optional, default: 10).
 *
 * @return bool True if the email is sent successfully, false otherwise.
 */
function sendOtpEmail($email, $name, $otp, $recipientName = 'User', $otpValidity = 10) {

    $mail = new PHPMailer(true);

    try {
        
        // SMTP configuration
        $mail->isSMTP();
        $mail->Host =  $_ENV['MAIL_HOST'] ?? '';
        $mail->SMTPAuth = true;
        $mail->Username =  $_ENV['MAIL_USERNAME'] ?? ''; 
        $mail->Password =  $_ENV['MAIL_PASSWORD'] ?? ''; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $_ENV['MAIL_PORT'] ?? '';
        
        // Sender and recipient settings
        $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'] ?? '', $_ENV['MAIL_FROM_NAME'] ?? '');
        $mail->addAddress($email, $name);

        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP Code';
        $mail->Body = '<div style="font-family: Arial, sans-serif; padding: 20px; border: 1px solid #ddd; border-radius: 5px; max-width: 600px; margin: auto;">
                        <h2 style="color: #4CAF50; text-align: center;">Your One-Time Password (OTP)</h2>
                        <p>Dear ' . htmlspecialchars($recipientName) . ',</p>
                        <p>Your One-Time Password (OTP) for accessing the Reserve Fund System is:</p>
                        <p style="font-size: 24px; font-weight: bold; color: #333; text-align: center; margin: 20px 0;">' . htmlspecialchars($otp) . '</p>
                        <p>This OTP is valid for ' . htmlspecialchars($otpValidity) . ' minutes. Please do not share this OTP with anyone.</p>
                        <p>To complete your login or verification, please enter the OTP in the system.</p>
                        <p>We are excited to have you on board and look forward to your participation in the system.</p>
                        <p>Thank you,<br>Reserve Funds Advisers Team</p>
                    </div>';

        // Send email
        $mail->send();
        return true; // Return true if email is sent successfully
    } catch (Exception $e) {
        rfa_create_log("[OTP] Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false; // Return false on failure
    }
}
