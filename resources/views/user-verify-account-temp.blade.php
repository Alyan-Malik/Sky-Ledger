<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Account Verification</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e1e1e1; border-radius: 10px;">
        <h2 style="color: #2d3748;">Verify Your Account</h2>
        
        <p>Dear <b>{{ $userFirstName }}</b>,</p>
        
        <p>
            We received a request to verify the <strong>{{ $appName }}</strong> account associated with 
            <span style="color: #4a90e2;">{{ $userEmail }}</span>.
        </p>

        <p>You can verify your account by clicking the button below:</p>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $actionLink }}" 
               target="_blank" 
               style="background-color: #38a169; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;">
                Verify Account
            </a>
        </div>

        <p style="font-size: 14px; color: #4a5568;">
            If you are having trouble with the button, copy and paste this link into your browser:
            <br>
            <a href="{{ $actionLink }}" target="_blank" style="color: #4a90e2; word-break: break-all;">
                {{ $actionLink }}
            </a>
        </p>

        <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 20px 0;">

        <p style="font-size: 13px; color: #718096;">
            If you did not create an account on {{ $appName }}, please ignore this email.
        </p>

        <p style="font-size: 12px; color: #a0aec0; text-align: center; margin-top: 20px;">
            This email was automatically sent by {{ $appName }}. Please do not reply to this message.
        </p>
    </div>
</body>
</html>
