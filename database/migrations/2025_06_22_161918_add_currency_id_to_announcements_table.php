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
            $table->foreignId('currency_id')->nullable()->constrained()->onDelete('set null')->after('vat');
            $table->dropColumn('currency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->string('currency', 3)->default('EUR')->after('vat');
            $table->dropForeign(['currency_id']);
            $table->dropColumn('currency_id');
        });
    }
};
