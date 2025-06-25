<?php

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
        Schema::table('plans', function (Blueprint $table) {
            // Delete old features column
            $table->dropColumn('features');

            // Add resources column with permissions
            $table->json('resources')->nullable()->comment('Resources with permissions: {"User": ["view", "create", "edit", "delete"], "Vehicle": ["view", "create"]}');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('resources');
            $table->json('features')->nullable();
        });
    }
};
