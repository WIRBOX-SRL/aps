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
        Schema::table('users', function (Blueprint $table) {
            $table->string('cloudinary_cloud_name')->nullable();
            $table->string('cloudinary_api_key')->nullable();
            $table->string('cloudinary_api_secret')->nullable();
            $table->string('cloudflare_api_key')->nullable();
            $table->string('cloudflare_zone_id')->nullable();
            $table->string('custom_domain')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'cloudinary_cloud_name',
                'cloudinary_api_key',
                'cloudinary_api_secret',
                'cloudflare_api_key',
                'cloudflare_zone_id',
                'custom_domain',
            ]);
        });
    }
};
