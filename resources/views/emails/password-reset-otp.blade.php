<!DOCTYPE html>
<html>
<head>
    <title>Password Reset OTP</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .otp { 
            font-size: 24px; 
            font-weight: bold; 
            letter-spacing: 3px; 
            text-align: center;
            margin: 20px 0;
            padding: 10px 20px;
            background: #f4f4f4;
            display: inline-block;
        }
        .footer { 
            margin-top: 30px; 
            font-size: 12px; 
            color: #666; 
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ config('app.name') }}</h1>
        </div>

        <p>Hello,</p>
        
        <p>You are receiving this email because we received a password reset request for your account.</p>
        
        <p>Your OTP is:</p>
        
        <div class="otp">{{ $otp }}</div>
        
        <p>This OTP will expire in {{ $expiresInMinutes }} minutes.</p>
        
        <p>If you did not request a password reset, no further action is required.</p>
        
        <div class="footer">
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </div>
    </div>
</body>
</html>
