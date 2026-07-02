<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('clients') && Schema::hasTable('client_confirmations')) {
            $this->addIndexes();
        } else {
            \Illuminate\Support\Facades\Event::listen(
                \Illuminate\Database\Events\MigrationsEnded::class,
                function () {
                    $this->addIndexes();
                }
            );
        }
    }

    protected function addIndexes(): void
    {
        try {
            Schema::table('clients', function (Blueprint $table) {
                $table->index('created_at', 'idx_clients_created_at');
            });
        } catch (\Illuminate\Database\QueryException $e) {
            $isDuplicate = $e->getCode() === '42P07' 
                || (Schema::getConnection()->getDriverName() === 'sqlite' && str_contains($e->getMessage(), 'already exists'));

            if (!$isDuplicate) {
                throw $e;
            }
        }

        try {
            Schema::table('client_confirmations', function (Blueprint $table) {
                $table->index('current_position', 'idx_confirmations_current_position');
                $table->index('back_to_jp_type', 'idx_confirmations_back_to_jp_type');

                $table->index(
                    ['current_position', 'back_to_jp_type'],
                    'idx_confirmations_position_jp_type'
                );
            });
        } catch (\Illuminate\Database\QueryException $e) {
            $isDuplicate = $e->getCode() === '42P07' 
                || (Schema::getConnection()->getDriverName() === 'sqlite' && str_contains($e->getMessage(), 'already exists'));

            if (!$isDuplicate) {
                throw $e;
            }
        }
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
