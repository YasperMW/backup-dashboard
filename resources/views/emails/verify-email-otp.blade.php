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
        .code {
            font-size: 32px;
            font-weight: bold;
            color: #4F46E5;
            text-align: center;
            padding: 20px;
            background-color: #f3f4f6;
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
        
        <p>Please use the following code to verify your email address:</p>
        
        <div class="code">{{ $code }}</div>
        
        <p>This code will expire in 10 minutes.</p>
        
        <p>If you did not create an account, no further action is required.</p>
        
        <div class="footer">
            <p>This email was sent to {{ $user->email }}. If you did not request this code, please ignore it.</p>
        </div>
    </div>
</body>
</html> 