<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->index('created_at', 'idx_clients_created_at');
        });

        Schema::table('client_confirmations', function (Blueprint $table) {
            $table->index('current_position', 'idx_confirmations_current_position');
            $table->index('back_to_jp_type', 'idx_confirmations_back_to_jp_type');

            $table->index(
                ['current_position', 'back_to_jp_type'],
                'idx_confirmations_position_jp_type'
            );
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex('idx_clients_created_at');
        });

        Schema::table('client_confirmations', function (Blueprint $table) {
            $table->dropIndex('idx_confirmations_current_position');
            $table->dropIndex('idx_confirmations_back_to_jp_type');
            $table->dropIndex('idx_confirmations_position_jp_type');
        });
    }
};
