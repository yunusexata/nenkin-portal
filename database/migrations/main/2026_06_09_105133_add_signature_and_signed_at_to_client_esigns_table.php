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
        Schema::table('client_esigns', function (Blueprint $table) {
            $table->string('user_agent')->nullable()->after('ip_address');
            $table->longText('signature_data')->nullable()->after('document_hash');
            $table->timestamp('signed_at')->nullable()->after('signature_data');
        });

        Schema::table('_history_client_esigns', function (Blueprint $table) {
            $table->string('user_agent')->nullable()->after('ip_address');
            $table->longText('signature_data')->nullable()->after('document_hash');
            $table->timestamp('signed_at')->nullable()->after('signature_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_esigns', function (Blueprint $table) {
            $table->dropColumn(['user_agent', 'signature_data', 'signed_at']);
        });

        Schema::table('_history_client_esigns', function (Blueprint $table) {
            $table->dropColumn(['user_agent', 'signature_data', 'signed_at']);
        });
    }
};
