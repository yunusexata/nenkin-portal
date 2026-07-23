<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for PT Sumber Rezeki Exata Indonesia.
     */
    public function up(): void
    {
        Schema::create('document_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('downloaded_at')->useCurrent();
            $table->enum('source', [
                'live_tracked',
                'infra_log',
                'proof_high',
                'notified_only',
            ])->default('live_tracked');
            $table->timestamps();

            // Indexes for fast auditing and client payroll queries
            $table->index('client_id', 'doc_downloads_client_id_idx');
            $table->index('downloaded_at', 'doc_downloads_downloaded_at_idx');
            $table->index('source', 'doc_downloads_source_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_downloads');
    }
};
