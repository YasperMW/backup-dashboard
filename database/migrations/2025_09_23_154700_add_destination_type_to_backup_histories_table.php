<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backup_histories', function (Blueprint $table) {
            if (!Schema::hasColumn('backup_histories', 'destination_type')) {
                $table->string('destination_type', 20)->nullable()->after('destination_directory')->index();
            }
        });

        // Best-effort backfill for existing rows
        try {
            $remotePath = config('backup.remote_path');
            if ($remotePath) {
                DB::table('backup_histories')
                    ->where('destination_directory', $remotePath)
                    ->update(['destination_type' => 'remote']);
            }
            // Set remaining nulls to local
            DB::table('backup_histories')
                ->whereNull('destination_type')
                ->update(['destination_type' => 'local']);
        } catch (\Throwable $e) {
            // Ignore backfill errors; column is created regardless
        }
    }

    public function down(): void
    {
        Schema::table('backup_histories', function (Blueprint $table) {
            if (Schema::hasColumn('backup_histories', 'destination_type')) {
                $table->dropColumn('destination_type');
            }
        });
    }
};
