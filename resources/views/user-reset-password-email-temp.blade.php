<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Password Changed Successfully</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e1e1e1; border-radius: 10px;">
        <h2 style="color: #2d3748;">Password Changed Successfully</h2>
        
        <p>Dear <b>{{ $user->name }}</b>,</p>
        
        <p>
            Your password for <strong>{{ $appName }}</strong> has been changed successfully. Here are your new login credentials:
        </p>

        <div style="background-color: #f7fafc; padding: 20px; border-radius: 8px; border: 1px solid #edf2f7; margin: 20px 0;">
            <p style="margin: 5px 0;">
                <strong style="color: #4a5568;">Login ID:</strong> 
                {{ isset($user->name) ? $user->name . ' or ' : '' }} {{ $user->email }}
            </p>
            <p style="margin: 5px 0;">
                <strong style="color: #4a5568;">Password:</strong> 
                <code style="background: #e2e8f0; padding: 2px 5px; border-radius: 4px;">{{ $newPassword }}</code>
            </p>
        </div>

        <p style="background-color: #fffaf0; color: #975a16; padding: 15px; border-radius: 5px; font-size: 14px; border-left: 4px solid #ed8936;">
            <strong>Security Warning:</strong> Please keep your credentials confidential. These are your private details and you should <strong>never share them</strong> with anybody else.
        </p>

        <p style="font-size: 13px; color: #718096; margin-top: 20px;">
            {{ $appName }} will not be liable for any misuse of your login ID or password.
        </p>

        <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 20px 0;">

        <p style="font-size: 12px; color: #a0aec0; text-align: center;">
            This email was automatically sent by {{ $appName }}. Please do not reply to this message.
        </p>
    </div>
</body>
</html>
