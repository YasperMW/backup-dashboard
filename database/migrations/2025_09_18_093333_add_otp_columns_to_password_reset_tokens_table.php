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
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->string('otp', 6)->nullable()->after('token');
            $table->timestamp('otp_created_at')->nullable()->after('otp');
            $table->integer('attempts')->default(0)->after('otp_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->dropColumn(['otp', 'otp_created_at', 'attempts']);
        });
    }
};
