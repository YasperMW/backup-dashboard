<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Services\MailService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\App;
use Laravel\Sanctum\HasApiTokens;
use PHPGangsta_GoogleAuthenticator;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'firstname',
        'lastname',
        'email',
        'password',
        'email_verification_code',
        'email_verification_code_expires_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verification_code',
        'email_verification_code_expires_at'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'email_verification_code_expires_at' => 'datetime'
        ];
    }

    public function sendEmailVerificationNotification()
    {
        $mailService = new MailService();
        // Always generate and send OTP code for email verification
        $code = random_int(100000, 999999);
        $this->email_verification_code = $code;
        $this->email_verification_code_expires_at = now()->addMinutes(10);
        $this->save();
        $mailService->sendVerificationOTP($this, $code);
    }

    public function sendTwoFactorCode()
    {
        $code = random_int(100000, 999999);
        $this->email_verification_code = $code;
        $this->email_verification_code_expires_at = now()->addMinutes(10);
        $this->save();

        $mailService = new MailService();
        $mailService->sendTwoFactorCode($this, $code);

        return $code;
    }

    public function verifyEmailWithOTP($code)
    {
        if ($this->email_verification_code === $code && 
            $this->email_verification_code_expires_at->isFuture()) {
            
            $this->email_verified_at = now();
            $this->email_verification_code = null;
            $this->email_verification_code_expires_at = null;
            $this->save();
            
            return true;
        }
        
        return false;
    }

    public function generateTwoFactorSecret()
    {
        $g = new PHPGangsta_GoogleAuthenticator();
        $secret = $g->createSecret();
        $this->two_factor_secret = encrypt($secret);
        $this->save();
        return $secret;
    }

    public function getTwoFactorSecret()
    {
        return $this->two_factor_secret ? decrypt($this->two_factor_secret) : null;
    }

    public function getTwoFactorQrCodeUrl()
    {
        $g = new PHPGangsta_GoogleAuthenticator();
        $appName = config('app.name', 'BackupDashboard');
        $email = $this->email;
        $secret = $this->getTwoFactorSecret();
        return $g->getQRCodeGoogleUrl($appName . ':' . $email, $secret, $appName);
    }

    public function verifyTwoFactorCode($code)
    {
        $g = new PHPGangsta_GoogleAuthenticator();
        $secret = $this->getTwoFactorSecret();
        return $g->verifyCode($secret, $code, 2); // 2 = 2*30sec clock tolerance
    }

    public function generateRecoveryCodes()
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4)));
        }
        $this->two_factor_recovery_codes = encrypt(json_encode($codes));
        $this->save();
        return $codes;
    }
}
