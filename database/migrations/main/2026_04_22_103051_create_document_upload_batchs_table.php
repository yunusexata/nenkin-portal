2<?php

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
            Schema::create('document_upload_batchs', function (Blueprint $table) {
                $this->scheme($table, false);
            });

            Schema::create('_history_document_upload_batchs', function (Blueprint $table) {
                $this->scheme($table, true);
            });
        }

        public function down()
        {
            Schema::dropIfExists('document_upload_batchs');
            Schema::dropIfExists('_history_document_upload_batchs');
        }

        private function scheme(Blueprint $table, $is_history = false)
        {

            $table->id();

            if ($is_history) {
                $table->bigInteger('obj_id')->unsigned();
            } else {
            }

            $table->unsignedBigInteger('uploaded_by');

            $table->unsignedInteger('total_files')
                ->default(0);

            $table->unsignedInteger('matched_files')
                ->default(0);

            $table->unsignedInteger('failed_files')
                ->default(0);

            $table->enum('status', [
                'processing',
                'completed',
                'partial_failed',
                'failed',
            ])->default('processing');

            $table->timestamp('completed_at')
                ->nullable();

            $table->bigInteger("created_by")->unsigned()->nullable();
            $table->bigInteger("updated_by")->unsigned()->nullable();
            $table->bigInteger("deleted_by")->unsigned()->nullable()->default(null);
            $table->softDeletes();
            $table->timestamps();
        }
    };
