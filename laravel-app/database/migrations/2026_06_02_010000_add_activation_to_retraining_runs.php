<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::table('retraining_runs', function (Blueprint $table) {
			$table->boolean('is_active')->default(false)->after('status')->index();
			$table->timestamp('activated_at')->nullable()->after('finished_at');
			$table->timestamp('archived_at')->nullable()->after('activated_at')->index();
		});
	}

	public function down(): void
	{
		Schema::table('retraining_runs', function (Blueprint $table) {
			$table->dropColumn(['is_active', 'activated_at', 'archived_at']);
		});
	}
};
