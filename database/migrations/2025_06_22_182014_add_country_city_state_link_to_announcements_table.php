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
        Schema::table('announcements', function (Blueprint $table) {
            $table->foreignId('country_id')->nullable()->constrained()->onDelete('set null')->after('vehicle_id');
            $table->foreignId('state_id')->nullable()->constrained()->onDelete('set null')->after('country_id');
            $table->foreignId('city_id')->nullable()->constrained()->onDelete('set null')->after('state_id');
            $table->string('link')->nullable()->after('city_id')->comment('External link for the announcement');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropForeign(['country_id', 'state_id', 'city_id']);
            $table->dropColumn(['country_id', 'state_id', 'city_id', 'link']);
        });
    }
};
