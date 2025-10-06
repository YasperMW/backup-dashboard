<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BackupJob extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'agent_id',
        'user_id',
        'name',
        'description',
        'source_path',
        'destination_path',
        'backup_type',
        'status',
        'error',
        'files_processed',
        'size_processed',
        'backup_path',
        'checksum',
        'size',
        'options',
        'started_at',
        'completed_at'
    ];

    protected $attributes = [
        'status' => 'pending',
        'files_processed' => 0,
        'size_processed' => 0,
    ];

    protected $casts = [
        'options' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'files_processed' => 'integer',
        'size_processed' => 'integer',
        'size' => 'integer',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    // Type constants
    const TYPE_FULL = 'full';
    const TYPE_INCREMENTAL = 'incremental';
    const TYPE_DIFFERENTIAL = 'differential';

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function markAsInProgress()
    {
        $this->update([
            'status' => self::STATUS_IN_PROGRESS,
            'started_at' => $this->started_at ?: now(),
        ]);
    }

    public function markAsCompleted($stats = [])
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'files_processed' => $stats['files_processed'] ?? $this->files_processed,
            'size_processed' => $stats['size_processed'] ?? $this->size_processed,
        ]);
    }

    public function markAsFailed($error = null)
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error' => $error,
            'completed_at' => now(),
        ]);
    }

    public function updateProgress($processed, $size)
    {
        $this->update([
            'files_processed' => $processed,
            'size_processed' => $size,
        ]);
    }
}
