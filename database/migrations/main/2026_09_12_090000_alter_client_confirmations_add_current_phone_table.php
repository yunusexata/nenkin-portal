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
        Schema::table('client_confirmations', function (Blueprint $table) {
            $table->string('current_phone', 30)->nullable()->after('nik');
        });

        Schema::table('_history_client_confirmations', function (Blueprint $table) {
            $table->string('current_phone', 30)->nullable()->after('nik');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_confirmations', function (Blueprint $table) {
            $table->dropColumn('current_phone');
        });

        Schema::table('_history_client_confirmations', function (Blueprint $table) {
            $table->dropColumn('current_phone');
        });
    }
};
