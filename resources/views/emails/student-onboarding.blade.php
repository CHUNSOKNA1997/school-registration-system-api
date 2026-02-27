<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Starlight Student Account</title>
</head>
<body style="margin:0;padding:24px;background:#ffffff;color:#111111;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.6;">
    <div style="max-width:640px;margin:0 auto;">
        <p style="margin:0 0 16px;"><strong>Starlight School</strong></p>

        <p style="margin:0 0 16px;">Hello,</p>

        <p style="margin:0 0 16px;">
            Your registration and payment have been confirmed.
            Please activate your student account to continue.
        </p>

        <p style="margin:0 0 8px;"><strong>Student login email</strong></p>
        <p style="margin:0 0 16px;">{{ $schoolEmail }}</p>

        <p style="margin:0 0 8px;"><strong>Activation link</strong></p>
        <p style="margin:0 0 16px;word-break:break-word;">
            <a href="{{ $activationUrl }}" style="color:#0b57d0;text-decoration:underline;">{{ $activationUrl }}</a>
        </p>

        <p style="margin:0 0 16px;">This link expires in {{ $expiresInHours }} hour(s).</p>

        <p style="margin:0 0 16px;">
            If you did not request this account, please ignore this email.
        </p>

        <p style="margin:0;">Regards,<br>Starlight School</p>
    </div>
</body>
</html>
