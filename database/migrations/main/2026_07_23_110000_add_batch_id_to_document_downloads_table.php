<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to add batch_id to document_downloads for PT Sumber Rezeki Exata Indonesia.
     */
    public function up(): void
    {
        Schema::table('document_downloads', function (Blueprint $table) {
            $table->foreignId('batch_id')
                ->nullable()
                ->after('client_id')
                ->constrained('document_upload_batchs')
                ->nullOnDelete();

            $table->index('batch_id', 'doc_downloads_batch_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_downloads', function (Blueprint $table) {
            $table->dropForeign(['batch_id']);
            $table->dropIndex('doc_downloads_batch_id_idx');
            $table->dropColumn('batch_id');
        });
    }
};
