<!DOCTYPE html>
<html>
<head>
    <title>Verify Your Email Address</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4F46E5;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Verify Your Email Address</h2>
        
        <p>Hello {{ $user->name }},</p>
        
        <p>Thank you for registering! Please click the button below to verify your email address:</p>
        
        <a href="{{ $verificationUrl }}" class="button">Verify Email Address</a>
        
        <p>If you did not create an account, no further action is required.</p>
        
        <p>If you're having trouble clicking the button, copy and paste the following URL into your web browser:</p>
        <p>{{ $verificationUrl }}</p>
        
        <div class="footer">
            <p>This email was sent to {{ $user->email }}. If you did not request this email, please ignore it.</p>
        </div>
    </div>
</body>
</html> 