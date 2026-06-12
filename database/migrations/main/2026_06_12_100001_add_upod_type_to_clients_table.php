<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('upod_type')->nullable()->default(null);
        });

        Schema::table('_history_clients', function (Blueprint $table) {
            $table->string('upod_type')->nullable()->default(null);
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('upod_type');
        });

        Schema::table('_history_clients', function (Blueprint $table) {
            $table->dropColumn('upod_type');
        });
    }
};
