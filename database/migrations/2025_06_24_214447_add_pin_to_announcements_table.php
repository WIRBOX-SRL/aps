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
            $table->string('pin', 8)->nullable()->after('id'); // 8 character unique PIN
        });

        // Generate PINs for existing announcements
        $announcements = \App\Models\Announcement::whereNull('pin')->get();
        foreach ($announcements as $announcement) {
            $announcement->update(['pin' => \App\Models\Announcement::generateUniquePin()]);
        }

        // Make pin NOT NULL after populating
        Schema::table('announcements', function (Blueprint $table) {
            $table->string('pin', 8)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn('pin');
        });
    }
};
