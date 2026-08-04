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
        Schema::table('users', function (Blueprint $table) {
            $table->string('provider_name')->nullable()->after('password');
            $table->string('provider_id')->nullable()->after('provider_name');
            // Make password nullable if you want users to register EXCLUSIVELY via Google without setting a password initially
            $table->string('password')->nullable()->change();
        });

        Schema::table('_history_users', function (Blueprint $table) {
            $table->string('provider_name')->nullable()->after('password');
            $table->string('provider_id')->nullable()->after('provider_name');
            // Make password nullable if you want users to register EXCLUSIVELY via Google without setting a password initially
            $table->string('password')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('provider_name');
            $table->dropColumn('provider_id');
        });

        Schema::table('_history_users', function (Blueprint $table) {
            $table->dropColumn('provider_name');
            $table->dropColumn('provider_id');
        });
    }
};
