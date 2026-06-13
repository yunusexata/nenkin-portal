<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            // 1. Drop constraints first
            // In PostgreSQL, constraints must be dropped before dropping the underlying unique index.
            DB::statement('ALTER TABLE _history_client_magic_links DROP CONSTRAINT IF EXISTS _history_client_magic_links_token_unique');
            DB::statement('ALTER TABLE _history_reviews DROP CONSTRAINT IF EXISTS _history_reviews_client_id_unique');

            // 2. Drop indexes second (in case they were created as index-only, or left over)
            DB::statement('DROP INDEX IF EXISTS _history_client_magic_links_token_unique');
            DB::statement('DROP INDEX IF EXISTS _history_reviews_client_id_unique');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('_history_client_magic_links', function (Blueprint $table) {
            $table->unique('token', '_history_client_magic_links_token_unique');
        });

        Schema::table('_history_reviews', function (Blueprint $table) {
            $table->unique('client_id', '_history_reviews_client_id_unique');
        });
    }
};
