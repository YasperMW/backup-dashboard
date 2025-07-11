<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('backup_schedules', function (Blueprint $table) {
            $table->integer('retention_days')->nullable()->after('enabled');
            $table->integer('max_backups')->nullable()->after('retention_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('backup_schedules', function (Blueprint $table) {
            $table->dropColumn(['retention_days', 'max_backups']);
        });
    }
};
