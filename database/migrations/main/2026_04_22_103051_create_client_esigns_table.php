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
        Schema::create('client_esigns', function (Blueprint $table) {
            $this->scheme($table, false);
        });

        Schema::create('_history_client_esigns', function (Blueprint $table) {
            $this->scheme($table, true);
        });
    }

    public function down()
    {
        Schema::dropIfExists('client_esigns');
        Schema::dropIfExists('_history_client_esigns');
    }

    private function scheme(Blueprint $table, $is_history = false)
    {

        $table->id();

        if ($is_history) {
            $table->bigInteger('obj_id')->unsigned();
        } else {
            $table->index('client_id', 'client_esigns_client_id_idx');
        }
        $table->unsignedBigInteger('client_id');

        $table->string('nik')->nullable();
        $table->text('address')->nullable();
        $table->string('agreement_version')->nullable();
        $table->string('ip_address')->nullable();
        $table->string('document_hash')->nullable();

        // E-Sign
        $table->text('stored_name')->nullable();         // uuid filename

        $table->string('disk')->nullable();                // local / s3
        $table->text('path')->nullable();                // storage path
        $table->text('note')->nullable();                // Attachment Note

        $table->string('extension')->nullable();           // jpg
        $table->string('mime_type')->nullable();           // image/jpeg
        $table->string('file_size')->nullable();           // bytes

        $table->bigInteger("created_by")->unsigned()->nullable();
        $table->bigInteger("updated_by")->unsigned()->nullable();
        $table->bigInteger("deleted_by")->unsigned()->nullable()->default(null);
        $table->softDeletes();
        $table->timestamps();
    }
};
