<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('retraining_runs', function (Blueprint $table) {
			$table->id();
			$table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
			$table->string('status', 30)->default('queued')->index();
			$table->string('stage', 60)->nullable();
			$table->unsignedTinyInteger('progress')->default(0);
			$table->string('message')->nullable();
			$table->json('selected_dataset_ids')->nullable();
			$table->json('selected_model_keys')->nullable();
			$table->string('combined_dataset_path')->nullable();
			$table->string('progress_path')->nullable();
			$table->json('result')->nullable();
			$table->text('error_message')->nullable();
			$table->timestamp('started_at')->nullable();
			$table->timestamp('finished_at')->nullable();
			$table->timestamps();
		});

		Schema::create('history_retraining_usages', function (Blueprint $table) {
			$table->id();
			$table->foreignId('history_id')->constrained('histories')->cascadeOnDelete();
			$table->foreignId('retraining_dataset_id')->nullable()->constrained()->nullOnDelete();
			$table->foreignId('retraining_run_id')->nullable()->constrained()->nullOnDelete();
			$table->timestamp('imported_at')->nullable();
			$table->timestamp('used_at')->nullable();
			$table->timestamps();

			$table->unique('history_id');
			$table->index(['retraining_dataset_id', 'used_at']);
		});

		Schema::create('model_versions', function (Blueprint $table) {
			$table->id();
			$table->string('version_uid')->unique();
			$table->string('model_key', 40)->index();
			$table->string('model_name');
			$table->string('status', 30)->default('available')->index();
			$table->boolean('is_active')->default(false)->index();
			$table->boolean('is_default')->default(false)->index();
			$table->json('metrics')->nullable();
			$table->json('evaluation_metrics')->nullable();
			$table->json('training_metrics')->nullable();
			$table->json('eligibility')->nullable();
			$table->text('artifact_model_path')->nullable();
			$table->text('artifact_features_path')->nullable();
			$table->text('artifact_metrics_path')->nullable();
			$table->foreignId('retraining_run_id')->nullable()->constrained()->nullOnDelete();
			$table->timestamp('retrained_at')->nullable();
			$table->timestamp('activated_at')->nullable();
			$table->timestamps();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('model_versions');
		Schema::dropIfExists('history_retraining_usages');
		Schema::dropIfExists('retraining_runs');
	}
};
