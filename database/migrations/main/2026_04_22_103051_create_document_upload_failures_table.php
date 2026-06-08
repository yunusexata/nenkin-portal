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
            Schema::create('document_upload_failures', function (Blueprint $table) {
                $this->scheme($table, false);
            });

            Schema::create('_history_document_upload_failures', function (Blueprint $table) {
                $this->scheme($table, true);
            });
        }

        public function down()
        {
            Schema::dropIfExists('document_upload_failures');
            Schema::dropIfExists('_history_document_upload_failures');
        }

        private function scheme(Blueprint $table, $is_history = false)
        {

            $table->id();

            if ($is_history) {
                $table->bigInteger('obj_id')->unsigned();
            } else {
                $table->index('batch_id', 'document_uf_batch_id_idx');
                $table->index('resolved_client_id', 'document_uf_resolved_client_id_idx');
                $table->index('folder_type', 'document_uf_folder_type_idx');
            }
            $table->unsignedBigInteger('batch_id');

            $table->string('original_filename');

            $table->string('folder_type', 50);

            $table->string('failure_reason', 500);

            $table->string('best_match_name')
                ->nullable();

            $table->decimal('best_match_score', 4, 3)
                ->nullable();

            $table->unsignedBigInteger('resolved_client_id');

            $table->timestamp('resolved_at')
                ->nullable();

            $table->bigInteger("created_by")->unsigned()->nullable();
            $table->bigInteger("updated_by")->unsigned()->nullable();
            $table->bigInteger("deleted_by")->unsigned()->nullable()->default(null);
            $table->softDeletes();
            $table->timestamps();
        }
    };
