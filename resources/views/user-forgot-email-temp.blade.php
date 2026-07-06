<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reset Password</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e1e1e1; border-radius: 10px;">
        <h2 style="color: #2d3748;">Password Reset Request</h2>
        
        <p>Dear <b>{{ $user->name }}</b>,</p>
        
        <p>
            You are receiving this email because you requested to reset your password on 
            <strong>{{ $appName }}</strong>.
        </p>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $actionLink }}" 
               target="_blank" 
               style="background-color: #4a90e2; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;">
                Reset Password
            </a>
        </div>

        <p>
            If you are having trouble with the button above, copy and paste the link below into your web browser:
            <br>
            <a href="{{ $actionLink }}" target="_blank" style="color: #4a90e2; word-break: break-all;">
                {{ $actionLink }}
            </a>
        </p>

        <p style="background-color: #fff5f5; color: #c53030; padding: 10px; border-radius: 5px; font-size: 14px;">
            <strong>Note:</strong> This password reset link is only valid for the next 15 minutes.
        </p>

        <p style="font-size: 13px; color: #718096;">
            IF YOU DID NOT REQUEST A PASSWORD RESET, PLEASE IGNORE THIS EMAIL.
        </p>

        <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 20px 0;">

        <p style="font-size: 12px; color: #a0aec0; text-align: center;">
            This email was automatically sent by {{ $appName }}. Please do not reply to this message.
        </p>
    </div>
</body>
</html>