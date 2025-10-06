<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Agent extends Authenticatable
{
    use HasApiTokens, SoftDeletes;

    protected $fillable = [
        'name',
        'hostname',
        'ip_address',
        'os',
        'token',
        'last_seen_at',
        'capabilities',
        'status',
        'version',
        'user_id'
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'capabilities' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function backupJobs()
    {
        return $this->hasMany(BackupJob::class);
    }

    public static function generateToken()
    {
        return hash('sha256', Str::random(40));
    }

    public function isOnline()
    {
        return $this->status === 'online' && 
               $this->last_seen_at && 
               $this->last_seen_at->diffInMinutes(now()) < 5;
    }

    public function markAsOnline()
    {
        $this->update([
            'status' => 'online',
            'last_seen_at' => now(),
        ]);
    }

    public function markAsOffline()
    {
        $this->update(['status' => 'offline']);
    }
}
