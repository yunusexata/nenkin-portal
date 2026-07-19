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

            $table->unsignedBigInteger('client_closing_history_id')->nullable();
            $table->string('client_closing_source_file_name')->nullable();
            $table->string('client_closing_batch_number')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->index('client_closing_source_file_name', 'client_c_closing_source_file_name_idx');
            $table->index('client_closing_batch_number', 'client_c_closing_batch_number_idx');
        });

        Schema::table('_history_client_confirmations', function (Blueprint $table) {
            $table->unsignedBigInteger('client_closing_history_id')->nullable();
            $table->string('client_closing_source_file_name')->nullable();
            $table->string('client_closing_batch_number')->nullable();
            $table->dateTime('closed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_confirmations', function (Blueprint $table) {
            $table->dropColumn('client_closing_history_id');
            $table->dropColumn('client_closing_source_file_name');
            $table->dropColumn('client_closing_batch_number');
            $table->dropColumn('closed_at');
        });

        Schema::table('_history_client_confirmations', function (Blueprint $table) {
            $table->dropColumn('client_closing_history_id');
            $table->dropColumn('client_closing_source_file_name');
            $table->dropColumn('client_closing_batch_number');
            $table->dropColumn('closed_at');
        });
    }
};
