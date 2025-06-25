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
            $table->foreignId('seller_id')->nullable()->constrained()->onDelete('set null')->after('user_id');
            $table->foreignId('vehicle_id')->nullable()->constrained()->onDelete('set null')->after('seller_id');
            $table->string('currency', 3)->default('EUR')->after('vat')->comment('Currency code (EUR, USD, etc.)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropForeign(['seller_id', 'vehicle_id']);
            $table->dropColumn(['seller_id', 'vehicle_id', 'currency']);
        });
    }
};
