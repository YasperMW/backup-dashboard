<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration fixes existing backup_histories records that have NULL user_id
     * by attempting to match them with their corresponding backup_jobs.
     */
    public function up(): void
    {
        // Strategy 1: Match by filename and approximate timestamp
        // Find backup_histories with NULL user_id and try to match them to backup_jobs
        DB::statement("
            UPDATE backup_histories bh
            INNER JOIN backup_jobs bj ON (
                bj.backup_path LIKE CONCAT('%', bh.filename)
                AND bj.source_path = bh.source_directory
                AND ABS(TIMESTAMPDIFF(SECOND, bj.created_at, bh.created_at)) < 300
                AND bj.user_id IS NOT NULL
            )
            SET bh.user_id = bj.user_id
            WHERE bh.user_id IS NULL
        ");

        // Strategy 2: For remaining NULL records, try matching by source directory and timestamp (wider window)
        DB::statement("
            UPDATE backup_histories bh
            INNER JOIN backup_jobs bj ON (
                bj.source_path = bh.source_directory
                AND ABS(TIMESTAMPDIFF(SECOND, bj.created_at, bh.created_at)) < 900
                AND bj.user_id IS NOT NULL
            )
            SET bh.user_id = bj.user_id
            WHERE bh.user_id IS NULL
            AND NOT EXISTS (
                SELECT 1 FROM backup_jobs bj2 
                WHERE bj2.source_path = bh.source_directory 
                AND bj2.user_id != bj.user_id
                AND ABS(TIMESTAMPDIFF(SECOND, bj2.created_at, bh.created_at)) < 900
            )
        ");

        // Strategy 3: Match by source directory only (for very recent backups, within same day)
        DB::statement("
            UPDATE backup_histories bh
            INNER JOIN (
                SELECT DISTINCT source_path, user_id
                FROM backup_jobs
                WHERE user_id IS NOT NULL
                AND DATE(created_at) = CURDATE()
            ) bj ON bj.source_path = bh.source_directory
            SET bh.user_id = bj.user_id
            WHERE bh.user_id IS NULL
            AND DATE(bh.created_at) = CURDATE()
            AND NOT EXISTS (
                SELECT 1 FROM backup_jobs bj2 
                WHERE bj2.source_path = bh.source_directory 
                AND bj2.user_id != bj.user_id
                AND DATE(bj2.created_at) = CURDATE()
            )
        ");

        // Log remaining NULL records for manual review
        $remaining = DB::table('backup_histories')
            ->whereNull('user_id')
            ->count();
        
        if ($remaining > 0) {
            \Log::warning("Migration: {$remaining} backup_histories records still have NULL user_id after automatic matching. These may need manual review.");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration cannot be safely reversed as we don't know which user_id values
        // were set by this migration vs. which were set correctly from the start.
        // Leaving user_id values as-is on rollback.
        \Log::info('Migration rollback: user_id values in backup_histories were not modified (cannot safely reverse).');
    }
};
