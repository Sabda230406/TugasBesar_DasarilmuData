<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('retraining_datasets', function (Blueprint $table) {
			$table->id();
			$table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
			$table->string('source_type', 30);
			$table->string('source_name');
			$table->string('stored_path')->nullable();
			$table->string('status', 30)->default('Valid')->index();
			$table->unsignedInteger('total_rows')->default(0);
			$table->unsignedInteger('valid_rows')->default(0);
			$table->unsignedInteger('stroke_0')->default(0);
			$table->unsignedInteger('stroke_1')->default(0);
			$table->json('preview')->nullable();
			$table->json('errors')->nullable();
			$table->timestamp('used_at')->nullable();
			$table->timestamp('archived_at')->nullable();
			$table->timestamps();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('retraining_datasets');
	}
};
