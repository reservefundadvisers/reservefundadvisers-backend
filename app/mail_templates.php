<?php
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
try {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
} catch (Exception $e) {
    // Handle the exception if .env file is missing or cannot be loaded
    // rfa_create_log("[OTP] Could not load .env file: " . $e->getMessage());
    return false;
}

$projectName = $_ENV['PROJECT_NAME'] ?? '';
$projectUrl = $_ENV['FRONTEND_URL'] ?? '';
$logoUrl = $_ENV['LOGO_URL'] ?? '';
$supportEmail = $_ENV['SUPPORT_EMAIL'] ?? '';

$currentYear = date('Y');

function emailTemplateOTP($recipientName, $otp, $validMinutes = 10)
{
    global $currentYear, $logoUrl, $supportEmail, $projectName;
    return [
        'subject' => 'Reserve Funds Advisers OTP Code',
        'body' => "<body style='margin:0;padding:0;background-color:#f4f6f8;font-family: Arial, Helvetica, sans-serif;'>
                    <table role='presentation' cellpadding='0' cellspacing='0' width='100%'>
                    <tr>
                        <td align='center' style='padding:20px 10px;'>
                        <table role='presentation' cellpadding='0' cellspacing='0' width='600' style='max-width:600px;background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 6px rgba(0,0,0,0.06)'>
                            <tr>
                            <td style='background:#0e519b;padding:22px 24px;color:#ffffff;text-align:center;'>
                                <!-- Non-clickable company logo; default to /img/logo.svg if not provided -->
                                <img src='$logoUrl' alt='$projectName logo' style='display:block;margin:0 auto;height:44px;width:auto;max-width:220px;' />

                                <!-- Heading below logo; kept whitespace and styling similar to screenshot -->
                                <h2 style='margin:12px 0 0 0;font-size:20px;font-weight:700;color:#ffffff;'>Your One-Time Password (OTP)</h2>
                            </td>
                            </tr>

                            <tr>
                                <td style='padding:28px 34px 0px;color:#21363a;'>
                                    <p style='margin:0 0 12px 0;font-size:14px;'>Dear <strong>" . htmlspecialchars($recipientName) . "</strong>,</p>

                                    <p style='margin:0 0 18px 0;font-size:14px;line-height:1.5;color:#4b5563;'>Your One-Time Password (OTP) for accessing the $projectName system is:</p>

                                    <div style='text-align:center;margin:12px 0 18px 0;padding:12px 0;border-radius:6px;'>
                                    <span style='display:inline-block;font-size:28px;font-weight:700;color:#fff;letter-spacing:2px;padding:8px 18px;border-radius:4px;background:#0e519b;border:1px solid #e0e0e0;'>" . htmlspecialchars($otp) . "</span>
                                    </div>

                                    <p style='margin:0 0 14px 0;font-size:14px;color:#4b5563;'>This OTP is valid for <strong> " . htmlspecialchars($validMinutes) . "</strong> minutes. Please do not share this OTP with anyone.</p>

                                    <p style='margin:0 0 14px 0;font-size:14px;color:#4b5563;'>To complete your login or verification, please enter the OTP in the system.</p>

                                    <p style='margin:0 0 18px 0;font-size:14px;color:#4b5563;'>We are excited to have you on board and look forward to your participation in the system.</p>

                                    <p style='margin:0;font-size:14px;color:#000; font-weight: 500;'>Thank you,<br/>$projectName Team</p>

                                    <hr style='border:none;border-top:1px solid #f0f5f8;margin:20px 0px 0px' />

                                    <p style='margin:0;font-size:12px;color:#94a3b8;'>In case of any Questions, Please Contact us at <a href='mailto: $supportEmail' style='color:#0e519b;text-decoration:none'> $supportEmail</a>.</p> 
                                </td>
                            </tr>

                            <tr>
                            <td style='background:#ffffff;padding:12px 24px 20px 24px;color:#94a3b8;font-size:12px;text-align:center;'>
                                <div> © $currentYear $projectName. All rights reserved.</div>
                            </td>
                            </tr>

                        </table>
                        </td>
                    </tr>
                    </table>
                </body>"
    ];
}

function emailTemplateForgotPassword($recipientName, $otp, $validMinutes = 10)
{
    return [
        'subject' => 'Reset Your Password',
        'body' => '<div style="font-family: Arial, sans-serif; padding: 20px; border: 1px solid #ddd; border-radius: 5px; max-width: 600px; margin: auto;">
                        <h2 style="color: #4CAF50; text-align: center;">Your One-Time Password (OTP)</h2>
                        <p>Dear ' . htmlspecialchars($recipientName) . ',</p>
                        <p>Your One-Time Password (OTP) for forgetting the password is:</p>
                        <p style="font-size: 24px; font-weight: bold; color: #333; text-align: center; margin: 20px 0;">' . htmlspecialchars($otp) . '</p>
                        <p>This OTP is valid for ' . htmlspecialchars($validMinutes) . ' minutes. Please do not share this OTP with anyone.</p>
                        <p>To complete your forgetting the password, please enter the OTP in the system.</p>
                        <p>Thank you,<br>Reserve Funds Advisers Team</p>
                    </div>'
    ];
}

function emailTemplateInviteMember($recipientName, $senderName, $inviteUrl, $projectName)
{
    global $currentYear, $logoUrl, $supportEmail;
    return [
        'subject' => "You're Invited to Join $projectName",
        'body' => "
        <body style='margin:0;padding:0;background-color:#f4f6f8;font-family: Arial, Helvetica, sans-serif;font-size:16px;'>
            <table role='presentation' cellpadding='0' cellspacing='0' width='100%'>
                <tr>
                    <td align='center' style='padding:20px 10px;'>
                        <table role='presentation' cellpadding='0' cellspacing='0' width='600' style='max-width:600px;background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 6px rgba(0,0,0,0.08)'>
                            <tr>
                                <td style='background:#0e519b;padding:24px 24px;color:#ffffff;'>
                                    <!-- Non-clickable logo (provide URL in '{{logoUrl}}') -->
                                    <img src='$logoUrl' alt='$projectName logo' style='display:block;margin:0 auto;height:40px;width:auto;max-width:220px;' />

                                    <!-- Heading -->
                                    <h1 style='margin:12px 0 0 0;font-size:20px;font-weight:600;text-align:center;'>Invitation to Join</h1>
                                </td>
                            </tr>
                            <tr>
                                <td style='padding:28px 24px;color:#263238;'>
                                    <p style='margin:0 0 16px 0;font-size:16px;'>Hello <strong>$recipientName</strong>,</p>

                                    <p style='margin:0 0 18px 0;font-size:16px;line-height:1.5;color:#334155;'>You have been invited to join <strong>$projectName</strong> by <strong>$senderName</strong>.</p>

                                    <table role='presentation' cellpadding='0' cellspacing='0' width='100%' style='margin-top:14px;margin-bottom:18px'>
                                        <tr>
                                            <td align='left'>
                                                <a href='$inviteUrl' style='display:inline-block;padding:12px 20px;background:#0e519b;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:600;'>Accept Invitation</a>
                                            </td>
                                        </tr>
                                    </table>

                                    <p style='margin:0;font-size:15px;color:#6b7280;line-height:20px'>If you were not expecting this invitation or you do not recognize the sender, you can safely ignore this email.</p>

                                    <hr style='border:none;border-top:1px solid #e6eef6;margin:20px 0' />

                                    <p style='margin:0;font-size:12px;color:#94a3b8;'>If you have any questions, contact us at <a href='mailto:$supportEmail' style='color:#0ea5e9;text-decoration:none'>$supportEmail</a>.</p>
                                </td>
                            </tr>
                            <tr>
                                <td style='background:#f8fafc;padding:14px 24px;color:#94a3b8;font-size:12px;text-align:center;'>
                                    <div>© $currentYear  Reserve Funds Advisers. All rights reserved.</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>"
    ];
}

function emailTemplateResetPasswordLink($recipientName, $senderName, $resetUrl, $projectName)
{
    global $currentYear, $logoUrl, $supportEmail;
    return [
        'subject' => "Reset Your Password",
        'body' => "
        <body style='margin:0;padding:0;background-color:#f4f6f8;font-family: Arial, Helvetica, sans-serif;font-size:16px;'>
            <table role='presentation' cellpadding='0' cellspacing='0' width='100%'>
                <tr>
                    <td align='center' style='padding:20px 10px;'>
                        <table role='presentation' cellpadding='0' cellspacing='0' width='600' style='max-width:600px;background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 6px rgba(0,0,0,0.08)'>
                            <tr>
                                <td style='background:#0e519b;padding:24px 24px;color:#ffffff;'>
                                    <!-- Non-clickable logo (provide URL in '{{logoUrl}}') -->
                                    <img src='$logoUrl' alt='$projectName logo' style='display:block;margin:0 auto;height:40px;width:auto;max-width:220px;' />

                                    <!-- Heading -->
                                    <h1 style='margin:12px 0 0 0;font-size:20px;font-weight:600;text-align:center;'>Reset Your Password</h1>
                                </td>
                            </tr>
                            <tr>
                                <td style='padding:28px 24px;color:#263238;'>
                                    <p style='margin:0 0 16px 0;font-size:16px;'>Hello <strong>$recipientName</strong>,</p>

                                    <p style='margin:0 0 18px 0;font-size:16px;line-height:1.5;color:#334155;'>A password reset was requested for your <strong>$projectName</strong> account by <strong>$senderName</strong>.</p>

                                    <table role='presentation' cellpadding='0' cellspacing='0' width='100%' style='margin-top:14px;margin-bottom:18px'>
                                        <tr>
                                            <td align='left'>
                                                <a href='$resetUrl' style='display:inline-block;padding:12px 20px;background:#0e519b;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:600;'>Reset Password</a>
                                            </td>
                                        </tr>
                                    </table>

                                    <p style='margin:0;font-size:15px;color:#6b7280;line-height:20px'>If you did not request this reset, you can safely ignore this email.</p>

                                    <hr style='border:none;border-top:1px solid #e6eef6;margin:20px 0' />

                                    <p style='margin:0;font-size:12px;color:#94a3b8;'>If you have any questions, contact us at <a href='mailto:$supportEmail' style='color:#0ea5e9;text-decoration:none'>$supportEmail</a>.</p>
                                </td>
                            </tr>
                            <tr>
                                <td style='background:#f8fafc;padding:14px 24px;color:#94a3b8;font-size:12px;text-align:center;'>
                                    <div>© $currentYear  Reserve Funds Advisers. All rights reserved.</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>"
    ];
}

function emailTemplatePublishModel($recipientName, $modelName, $accessUrl)
{
    return [
        'subject' => "Your AI Model '$modelName' is Published",
        'body' => "
        <div style='font-family: Arial; padding:20px;'>
            <h2>Model Published Successfully</h2>
            <p>Dear <strong>$recipientName</strong>,</p>
            <p>We are excited to inform you that your AI model '<strong>$modelName</strong>' has been successfully published.</p>
            <p>You can access your published model here:</p>
            <p style='text-align:center;'>
                <a href='$accessUrl' style='padding:10px 20px; background:#4CAF50; color:#fff; text-decoration:none; border-radius:5px;'>Access Your Model</a>
            </p>
            <p>Thank you for using our platform!</p>
        </div>"
    ];
}
