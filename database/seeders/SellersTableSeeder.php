<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SellersTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('sellers')->truncate();
        DB::table('sellers')->insert([
            [
                'id' => 1,
                'name' => 'User Seller 1',
                'email' => 'seller1@test.com',
                'phone' => '0123456789',
                'company' => 'User Company 1',
                'address' => 'User Address 1',
                'logo' => null,
                'website' => null,
                'user_id' => 3,
                'language' => json_encode(['en','fr']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'User Seller 2',
                'email' => 'seller2@test.com',
                'phone' => '0123456790',
                'company' => 'User Company 2',
                'address' => 'User Address 2',
                'logo' => null,
                'website' => null,
                'user_id' => 3,
                'language' => json_encode(['en']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'name' => 'Admin Seller',
                'email' => 'seller3@admin.com',
                'phone' => '0123456791',
                'company' => 'Admin Company',
                'address' => 'Admin Address',
                'logo' => null,
                'website' => null,
                'user_id' => 2,
                'language' => json_encode(['en']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
