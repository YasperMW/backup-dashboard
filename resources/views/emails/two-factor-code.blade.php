<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Two-Factor Authentication Code</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .code {
            font-size: 24px;
            font-weight: bold;
            color: #4F46E5;
            text-align: center;
            padding: 20px;
            background: #F3F4F6;
            border-radius: 8px;
            margin: 20px 0;
        }
        .expires {
            color: #6B7280;
            font-size: 14px;
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>Two-Factor Authentication Code</h1>
    <p>Your two-factor authentication code is:</p>
    
    <div class="code">{{ $code }}</div>
    
    <p class="expires">This code will expire in {{ $expiresIn }}.</p>
    
    <p>If you did not request this code, please secure your account immediately.</p>
    
    <p>Best regards,<br>Your Application Team</p>
</body>
</html> 