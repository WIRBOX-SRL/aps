<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AnnouncementsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('announcements')->truncate();
        DB::table('announcements')->insert([
            [
                'id' => 1,
                'title' => 'User Announcement',
                'user_id' => 3,
                'type' => 'general',
                'status' => 'published',
                'published_at' => now(),
                'expires_at' => null,
                'target_roles' => null,
                'is_pinned' => 0,
                'views_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
                'price' => 1000,
                'vat' => 19,
                'seller_id' => 1,
                'vehicle_id' => 1,
                'currency_id' => 1,
                'country_id' => 1,
                'state_id' => 1,
                'city_id' => 1,
                'link' => null,
            ],
            [
                'id' => 2,
                'title' => 'Admin Announcement',
                'user_id' => 2,
                'type' => 'general',
                'status' => 'published',
                'published_at' => now(),
                'expires_at' => null,
                'target_roles' => null,
                'is_pinned' => 0,
                'views_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
                'price' => 2000,
                'vat' => 19,
                'seller_id' => 3,
                'vehicle_id' => 2,
                'currency_id' => 1,
                'country_id' => 1,
                'state_id' => 1,
                'city_id' => 1,
                'link' => null,
            ],
        ]);
    }
}
