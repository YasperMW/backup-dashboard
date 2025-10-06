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
        if (!Schema::hasTable('backup_jobs')) {
            Schema::create('backup_jobs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('agent_id')->constrained('agents')->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('source_path');
                $table->string('destination_path');
                $table->enum('backup_type', ['full', 'incremental'])->default('full');
                $table->string('status')->default('pending'); // pending, in_progress, completed, failed
                $table->text('error')->nullable();
                $table->integer('files_processed')->default(0);
                $table->bigInteger('size_processed')->default(0);
                $table->string('backup_path')->nullable();
                $table->string('checksum')->nullable();
                $table->bigInteger('size')->nullable();
                $table->json('options')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
                
                // Indexes for better performance
                $table->index('status');
                $table->index('agent_id');
                $table->index('user_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backup_jobs');
    }
};
