# OTP-Based Password Reset Flow

This document explains the OTP (One-Time Password) based password reset flow implemented in the application.

## Overview

The password reset flow has been updated to use OTP (One-Time Password) instead of direct reset links. This adds an extra layer of security by requiring users to verify their identity using a 6-digit code sent to their email before they can reset their password.

## Flow

1. **Request Password Reset**
   - User enters their email address on the "Forgot Password" page
   - System generates a 6-digit OTP and stores it in the database
   - OTP is sent to the user's email address

2. **Verify OTP**
   - User is redirected to the OTP verification page
   - User enters the 6-digit OTP received via email
   - System verifies the OTP and its expiration
   - If valid, user is redirected to the password reset page

3. **Reset Password**
   - User enters and confirms their new password
   - System updates the user's password and logs them out of all devices
   - User is redirected to login page with success message

## Security Features

- **Rate Limiting**:
  - Max 3 OTP requests per minute per IP
  - Max 5 OTP requests per 10 minutes per email
  - Prevents brute force attacks

- **OTP Expiration**:
  - OTPs expire after 60 minutes (configurable in `config/auth.php`)
  - Expired OTPs cannot be used

- **One-Time Use**:
  - Each OTP can only be used once
  - After successful verification, the OTP is invalidated

- **Secure Token Handling**:
  - Uses Laravel's built-in password reset token system
  - Tokens are hashed in the database

## Database Changes

Added columns to `password_reset_tokens` table:
- `otp`: VARCHAR(6) - Stores the 6-digit OTP
- `otp_created_at`: TIMESTAMP - When the OTP was generated
- `attempts`: INT - Number of failed OTP attempts

## Configuration

OTP expiration time can be configured in `config/auth.php`:

```php
'passwords' => [
    'users' => [
        'provider' => 'users',
        'table' => 'password_reset_tokens',
        'expire' => 60, // OTP expires after 60 minutes
        'throttle' => 60, // Throttle reset attempts for 60 seconds
    ],
],
```

## Testing

To test the OTP flow:

1. Go to the Forgot Password page
2. Enter your email and submit
3. Check your email for the OTP
4. Enter the OTP on the verification page
5. Set a new password

## Troubleshooting

- **OTP Not Received**:
  - Check spam/junk folder
  - Verify email address is correct
  - Check application logs for email sending errors

- **Invalid OTP**:
  - Ensure you're entering the most recent OTP
  - OTPs are case-sensitive
  - OTPs expire after 60 minutes

- **Too Many Attempts**:
  - Wait for the rate limit to reset (1-10 minutes depending on attempt count)
  - Contact support if you're still having issues
