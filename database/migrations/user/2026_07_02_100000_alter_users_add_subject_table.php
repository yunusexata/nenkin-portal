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
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_type')->nullable();
            $table->index('subject_id', 'users_subject_id_idx');
            $table->index('subject_type', 'users_subject_type_idx');
        });

        Schema::table('_history_users', function (Blueprint $table) {
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_type')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('subject_id');
            $table->dropColumn('subject_type');
        });

        Schema::table('_history_users', function (Blueprint $table) {
            $table->dropColumn('subject_id');
            $table->dropColumn('subject_type');
        });
    }
};
