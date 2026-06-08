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
            Schema::create('documents', function (Blueprint $table) {
                $this->scheme($table, false);
            });

            Schema::create('_history_documents', function (Blueprint $table) {
                $this->scheme($table, true);
            });
        }

        public function down()
        {
            Schema::dropIfExists('documents');
            Schema::dropIfExists('_history_documents');
        }

        private function scheme(Blueprint $table, $is_history = false)
        {

            $table->id();

            if ($is_history) {
                $table->bigInteger('obj_id')->unsigned();
            } else {
                $table->index('client_id', 'documents_client_id_idx');

                $table->unique(
                    ['client_id', 'type'],
                    'uq_client_doc_type'
                );
            }

            $table->foreignId('client_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('type', [
                'ichijikin',
                'resi',
                'kwitansi',
            ]);

            $table->string('original_filename');
            $table->string('bucket_path', 500);

            $table->decimal('match_confidence', 4, 3)
                ->default(1.000);

            $table->unsignedBigInteger('batch_id')
                ->nullable();

            $table->unsignedBigInteger('uploaded_by');

            $table->timestamp('uploaded_at')
                ->useCurrent();

            $table->bigInteger("created_by")->unsigned()->nullable();
            $table->bigInteger("updated_by")->unsigned()->nullable();
            $table->bigInteger("deleted_by")->unsigned()->nullable()->default(null);
            $table->softDeletes();
            $table->timestamps();
        }
    };
