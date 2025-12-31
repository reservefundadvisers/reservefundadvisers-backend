<?php 

function emailTemplateOTP($recipientName, $otp, $validMinutes = 10) {
    return [
        'subject' => 'Your OTP Code',
        'body' => '<div style="font-family: Arial, sans-serif; padding: 20px; border: 1px solid #ddd; border-radius: 5px; max-width: 600px; margin: auto;">
                        <h2 style="color: #4CAF50; text-align: center;">Your One-Time Password (OTP)</h2>
                        <p>Dear ' . htmlspecialchars($recipientName) . ',</p>
                        <p>Your One-Time Password (OTP) for accessing the Reserve Fund System is:</p>
                        <p style="font-size: 24px; font-weight: bold; color: #333; text-align: center; margin: 20px 0;">' . htmlspecialchars($otp) . '</p>
                        <p>This OTP is valid for ' . htmlspecialchars($validMinutes) . ' minutes. Please do not share this OTP with anyone.</p>
                        <p>To complete your login or verification, please enter the OTP in the system.</p>
                        <p>We are excited to have you on board and look forward to your participation in the system.</p>
                        <p>Thank you,<br>Reserve Funds Advisers Team</p>
                    </div>'
    ];
}

function emailTemplateForgotPassword($recipientName, $otp, $validMinutes = 10) {
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

function emailTemplateInviteMember($recipientName, $senderName ,$inviteUrl, $projectName) {
    return [
        'subject' => "You're Invited to Join $projectName",
        'body' => "
        <div style='font-family: Arial; padding:20px;'>
            <h2>Invitation to Join</h2>
            <p>Hello <strong>$recipientName</strong>,</p>
            <p>You have been invited to join <strong>$projectName</strong>. by <strong>$senderName</strong>.</p>
            <p style='text-align:center;'>
                <a href='$inviteUrl' style='padding:10px 20px; background:#2196F3; color:#fff; text-decoration:none; border-radius:5px;'>Accept Invitation</a>
            </p>
            <p>If you were not expecting this invitation, please ignore this email.</p>
        </div>"
    ];
}

function emailTemplatePublishModel($recipientName, $modelName, $accessUrl) {
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
