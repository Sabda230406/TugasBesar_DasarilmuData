<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('histories') || Schema::hasColumn('histories', 'model_name')) {
            return;
        }

        Schema::table('histories', function (Blueprint $table) {
            $table->string('model_name')->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('histories') || ! Schema::hasColumn('histories', 'model_name')) {
            return;
        }

        Schema::table('histories', function (Blueprint $table) {
            $table->dropColumn('model_name');
        });
    }
};
