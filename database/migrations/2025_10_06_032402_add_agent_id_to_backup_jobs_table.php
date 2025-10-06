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
        Schema::table('backup_jobs', function (Blueprint $table) {
            // Add columns if they do not exist
            if (!Schema::hasColumn('backup_jobs', 'agent_id')) {
                $table->foreignId('agent_id')->after('id')->nullable()->constrained('agents')->nullOnDelete();
                $table->index('agent_id');
            }
            if (!Schema::hasColumn('backup_jobs', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->index('user_id');
            }
            if (!Schema::hasColumn('backup_jobs', 'name')) {
                $table->string('name')->nullable();
            }
            if (!Schema::hasColumn('backup_jobs', 'description')) {
                $table->text('description')->nullable();
            }
            if (!Schema::hasColumn('backup_jobs', 'source_path')) {
                $table->string('source_path')->nullable();
            }
            if (!Schema::hasColumn('backup_jobs', 'destination_path')) {
                $table->string('destination_path')->nullable();
            }
            if (!Schema::hasColumn('backup_jobs', 'backup_type')) {
                $table->enum('backup_type', ['full', 'incremental'])->default('full');
            }
            if (!Schema::hasColumn('backup_jobs', 'status')) {
                $table->string('status')->default('pending');
                $table->index('status');
            }
            if (!Schema::hasColumn('backup_jobs', 'error')) {
                $table->text('error')->nullable();
            }
            if (!Schema::hasColumn('backup_jobs', 'files_processed')) {
                $table->integer('files_processed')->default(0);
            }
            if (!Schema::hasColumn('backup_jobs', 'size_processed')) {
                $table->bigInteger('size_processed')->default(0);
            }
            if (!Schema::hasColumn('backup_jobs', 'backup_path')) {
                $table->string('backup_path')->nullable();
            }
            if (!Schema::hasColumn('backup_jobs', 'checksum')) {
                $table->string('checksum')->nullable();
            }
            if (!Schema::hasColumn('backup_jobs', 'size')) {
                $table->bigInteger('size')->nullable();
            }
            if (!Schema::hasColumn('backup_jobs', 'options')) {
                $table->json('options')->nullable();
            }
            if (!Schema::hasColumn('backup_jobs', 'started_at')) {
                $table->timestamp('started_at')->nullable();
            }
            if (!Schema::hasColumn('backup_jobs', 'completed_at')) {
                $table->timestamp('completed_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('backup_jobs', function (Blueprint $table) {
            //
        });
    }
};
