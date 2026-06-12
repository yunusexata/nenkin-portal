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
        Schema::create('client_confirmations', function (Blueprint $table) {
            $this->scheme($table, false);
        });

        Schema::create('_history_client_confirmations', function (Blueprint $table) {
            $this->scheme($table, true);
        });
    }

    public function down()
    {
        Schema::dropIfExists('client_confirmations');
        Schema::dropIfExists('_history_client_confirmations');
    }

    private function scheme(Blueprint $table, $is_history = false)
    {

        $table->id();

        if ($is_history) {
            $table->bigInteger('obj_id')->unsigned();
        } else {
            $table->index('client_id', 'client_confirmations_client_id_idx');
        }
        $table->unsignedBigInteger('client_id');

        // FORM SUBMISSION
        $table->string('nenkin_80_received_type')->nullable();
        $table->string('current_position')->nullable();
        $table->string('back_to_jp_type')->nullable();
        $table->string('departure_type')->nullable();
        $table->integer('departure_month')->nullable();
        $table->integer('departure_year')->nullable();
        $table->integer('estimated_return_month')->nullable();
        $table->integer('estimated_return_year')->nullable();
        $table->text('indonesia_address')->nullable();
        $table->string('nik')->nullable();
        $table->string('claim_type');

        $table->bigInteger("created_by")->unsigned()->nullable();
        $table->bigInteger("updated_by")->unsigned()->nullable();
        $table->bigInteger("deleted_by")->unsigned()->nullable()->default(null);
        $table->softDeletes();
        $table->timestamps();
    }
};
