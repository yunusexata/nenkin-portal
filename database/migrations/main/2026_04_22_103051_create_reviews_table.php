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
            Schema::create('reviews', function (Blueprint $table) {
                $this->scheme($table, false);
            });

            Schema::create('_history_reviews', function (Blueprint $table) {
                $this->scheme($table, true);
            });
        }

        public function down()
        {
            Schema::dropIfExists('reviews');
            Schema::dropIfExists('_history_reviews');
        }

        private function scheme(Blueprint $table, $is_history = false)
        {

            $table->id();

            if ($is_history) {
                $table->bigInteger('obj_id')->unsigned();
                $table->unsignedBigInteger('client_id');
            } else {
                $table->index('status', 'reviews_status_idx');
                $table->index('rating', 'reviews_rating_idx');
                $table->unsignedBigInteger('client_id')->unique();
            }

            $table->unsignedTinyInteger('rating');

            $table->text('message')
                ->nullable();

            $table->enum('status', [
                'intercepted',
                'forwarded',
                'resolved',
            ])->default('intercepted');

            $table->unsignedBigInteger('resolved_by')->nullable();

            $table->timestamp('resolved_at')
                ->nullable();

            $table->text('notes')
                ->nullable();

            $table->timestamp('submitted_at')
                ->useCurrent();

            $table->bigInteger("created_by")->unsigned()->nullable();
            $table->bigInteger("updated_by")->unsigned()->nullable();
            $table->bigInteger("deleted_by")->unsigned()->nullable()->default(null);
            $table->softDeletes();
            $table->timestamps();
        }
    };
