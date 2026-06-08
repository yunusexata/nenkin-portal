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
        Schema::create('clients', function (Blueprint $table) {
            $this->scheme($table, false);
        });

        Schema::create('_history_clients', function (Blueprint $table) {
            $this->scheme($table, true);
        });
    }

    public function down()
    {
        Schema::dropIfExists('clients');
        Schema::dropIfExists('_history_clients');
    }

    private function scheme(Blueprint $table, $is_history = false)
    {

        $table->id();

        if ($is_history) {
            $table->bigInteger('obj_id')->unsigned();
        } else {
            $table->index('no_jp', 'clients_no_jp_idx');
            $table->index('name_normalized', 'clients_name_normalized_idx');
            $table->index('claim_type', 'clients_claim_type_idx');
            $table->index('status', 'clients_status_idx');
        }
        $table->string('no_jp');
        $table->string('name');
        $table->string('name_normalized');
        $table->date('dob');
        $table->string('phone')->nullable();
        $table->string('claim_type');
        $table->string('status');
        $table->json('raw_data');
        $table->string('source_file_name');
        $table->string('source_file_disk')->nullable();                // local / s3
        $table->text('source_file_path')->nullable();                // storage path
        $table->dateTime('imported_at');
        $table->string('facebook')->nullable();
        $table->string('instagram')->nullable();
        $table->string('pic');
        $table->string('lpk')->nullable();
        $table->text('note')->nullable();

        $table->bigInteger("created_by")->unsigned()->nullable();
        $table->bigInteger("updated_by")->unsigned()->nullable();
        $table->bigInteger("deleted_by")->unsigned()->nullable()->default(null);
        $table->softDeletes();
        $table->timestamps();
    }
};
