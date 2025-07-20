<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupSchedule extends Model
{
    protected $fillable = [
        'frequency',
        'time',
        'days_of_week',
        'source_directories',
        'destination_directory',
        'enabled',
        'retention_days',
        'max_backups',
        'user_id',
    ];
    protected $casts = [
        'source_directories' => 'array',
        'enabled' => 'boolean',
    ];
}
