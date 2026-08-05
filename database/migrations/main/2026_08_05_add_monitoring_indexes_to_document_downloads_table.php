<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Query monitoring utama: client + batch + source
        Schema::table('document_downloads', function (Blueprint $table) {
            $table->index(['client_id', 'batch_id', 'source'], 'dd_monitoring_client_batch_source_idx');
        });
    }

    public function down(): void
    {
        Schema::table('document_downloads', function (Blueprint $table) {
            $table->dropIndex('dd_monitoring_client_batch_source_idx');
        });
    }
};
