<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backup_histories', function (Blueprint $table) {
            $table->string('backup_type')->default('full');
            $table->string('compression_level')->default('none');
        });
    }

    public function down(): void
    {
        Schema::table('backup_histories', function (Blueprint $table) {
            $table->dropColumn(['backup_type', 'compression_level']);
        });
    }
}; 