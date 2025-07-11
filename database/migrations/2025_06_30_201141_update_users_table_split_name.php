<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate existing data
        DB::table('users')->get()->each(function ($user) {
            if (property_exists($user, 'name') && $user->name) {
                $parts = explode(' ', $user->name, 2);
                $firstname = $parts[0];
                $lastname = $parts[1] ?? '';
                DB::table('users')->where('id', $user->id)->update([
                    'firstname' => $firstname,
                    'lastname' => $lastname,
                ]);
            }
        });

        // Drop 'name' column if it exists
        if (Schema::hasColumn('users', 'name')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->after('id');
        });

        // Migrate data back
        DB::table('users')->get()->each(function ($user) {
            $name = trim($user->firstname . ' ' . $user->lastname);
            DB::table('users')->where('id', $user->id)->update([
                'name' => $name,
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('firstname');
            $table->dropColumn('lastname');
        });
    }
};
