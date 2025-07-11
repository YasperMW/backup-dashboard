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
        Schema::create('backup_histories', function (Blueprint $table) {
            $table->id();
            $table->string('source_directory');
            $table->string('destination_directory');
            $table->string('filename');
            $table->bigInteger('size')->nullable();
            $table->string('status')->default('pending'); // pending, completed, failed
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('integrity_hash')->nullable();
            $table->timestamp('integrity_verified_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backup_histories');
    }
};
