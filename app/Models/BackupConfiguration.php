<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupConfiguration extends Model
{
    protected $fillable = [
        'storage_location',
        'backup_type',
        'compression_level',
        'retention_period',
    ];
} 