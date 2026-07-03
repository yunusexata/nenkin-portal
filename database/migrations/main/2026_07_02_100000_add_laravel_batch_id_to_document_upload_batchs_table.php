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
        Schema::table('document_upload_batchs', function (Blueprint $table) {
            $table->string('laravel_batch_id')->nullable()->after('uploaded_by');
        });

        Schema::table('_history_document_upload_batchs', function (Blueprint $table) {
            $table->string('laravel_batch_id')->nullable()->after('uploaded_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_upload_batchs', function (Blueprint $table) {
            $table->dropColumn('laravel_batch_id');
        });

        Schema::table('_history_document_upload_batchs', function (Blueprint $table) {
            $table->dropColumn('laravel_batch_id');
        });
    }
};
