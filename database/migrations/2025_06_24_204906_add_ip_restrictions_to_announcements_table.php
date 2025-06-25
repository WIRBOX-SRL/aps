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
            $table->json('allowed_ips')->nullable()->after('target_roles'); // Array of allowed IP addresses
            $table->integer('max_ip_access_count')->default(0)->after('allowed_ips'); // 0 = unlimited
            $table->json('ip_access_log')->nullable()->after('max_ip_access_count'); // Track IP access
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn(['allowed_ips', 'max_ip_access_count', 'ip_access_log']);
        });
    }
};
