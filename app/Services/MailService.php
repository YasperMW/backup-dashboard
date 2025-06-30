<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService
{
    protected $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->configureMailer();
    }

    protected function configureMailer()
    {
        try {
            $this->mailer->isSMTP();
            $this->mailer->Host = config('mail.mailers.smtp.host');
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = config('mail.mailers.smtp.username');
            $this->mailer->Password = config('mail.mailers.smtp.password');
            $this->mailer->SMTPSecure = config('mail.mailers.smtp.encryption');
            $this->mailer->Port = config('mail.mailers.smtp.port');
            $this->mailer->setFrom(config('mail.from.address'), config('mail.from.name'));
        } catch (Exception $e) {
            throw new \Exception("Mail configuration failed: {$e->getMessage()}");
        }
    }

    public function sendVerificationEmail($user, $verificationUrl)
    {
        try {
            $this->mailer->addAddress($user->email, $user->name);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Verify Your Email Address';
            
            $body = view('emails.verify-email', [
                'user' => $user,
                'verificationUrl' => $verificationUrl
            ])->render();
            
            $this->mailer->Body = $body;
            $this->mailer->AltBody = strip_tags($body);
            
            return $this->mailer->send();
        } catch (Exception $e) {
            \Log::error('Email verification failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function sendVerificationOTP($user, $code)
    {
        try {
            $this->mailer->addAddress($user->email, $user->name);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Your Email Verification Code';
            
            $body = view('emails.verify-email-otp', [
                'user' => $user,
                'code' => $code
            ])->render();
            
            $this->mailer->Body = $body;
            $this->mailer->AltBody = strip_tags($body);
            
            return $this->mailer->send();
        } catch (Exception $e) {
            \Log::error('Email verification OTP failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function sendVerificationCode($email, $code)
    {
        try {
            $this->mailer->addAddress($email);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Email Verification Code';
            
            $body = view('emails.verification-code', [
                'code' => $code,
                'expiresIn' => '30 minutes'
            ])->render();
            
            $this->mailer->Body = $body;
            $this->mailer->AltBody = "Your verification code is: {$code}. This code will expire in 30 minutes.";

            return $this->mailer->send();
        } catch (Exception $e) {
            throw new \Exception("Failed to send verification code: {$e->getMessage()}");
        }
    }

    public function sendTwoFactorCode($email, $code)
    {
        try {
            $this->mailer->addAddress($email);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Two-Factor Authentication Code';
            
            $body = view('emails.two-factor-code', [
                'code' => $code,
                'expiresIn' => '5 minutes'
            ])->render();
            
            $this->mailer->Body = $body;
            $this->mailer->AltBody = "Your two-factor authentication code is: {$code}. This code will expire in 5 minutes.";

            return $this->mailer->send();
        } catch (Exception $e) {
            throw new \Exception("Failed to send two-factor code: {$e->getMessage()}");
        }
    }
} 