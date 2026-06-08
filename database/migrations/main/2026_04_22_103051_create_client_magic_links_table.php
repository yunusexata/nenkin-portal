<?php

use App\Enums\Gensen\JobStatus;
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
        Schema::create('client_magic_links', function (Blueprint $table) {
            $this->scheme($table, false);
        });

        Schema::create('_history_client_magic_links', function (Blueprint $table) {
            $this->scheme($table, true);
        });
    }

    public function down()
    {
        Schema::dropIfExists('client_magic_links');
        Schema::dropIfExists('_history_client_magic_links');
    }

    private function scheme(Blueprint $table, $is_history = false)
    {

        $table->id();

        if ($is_history) {
            $table->bigInteger('obj_id')->unsigned();
        } else {
            $table->index('token', 'client_magic_links_token_idx');
        }
        $table->unsignedBigInteger('client_id');
        $table->uuid('token')->unique();

        $table->string('email')->nullable();
        $table->string('password')->nullable();

        $table->dateTime('used_at')->nullable();

        $table->dateTime('expires_at');

        $table->bigInteger("created_by")->unsigned()->nullable();
        $table->bigInteger("updated_by")->unsigned()->nullable();
        $table->bigInteger("deleted_by")->unsigned()->nullable()->default(null);
        $table->softDeletes();
        $table->timestamps();
    }
};
