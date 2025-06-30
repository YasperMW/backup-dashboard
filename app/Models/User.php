<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Services\MailService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\App;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

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
        $this->two_factor_secret = $code;
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
}
