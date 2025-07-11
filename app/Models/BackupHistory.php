<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupHistory extends Model
{
    protected $fillable = [
        'source_directory',
        'destination_directory',
        'filename',
        'size',
        'status',
        'started_at',
        'completed_at',
        'integrity_hash',
        'integrity_verified_at',
        'error_message',
    ];
}
