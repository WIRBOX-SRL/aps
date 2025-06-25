<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VehiclesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('vehicles')->truncate();
        DB::table('vehicles')->insert([
            [
                'id' => 1,
                'title' => 'User Vehicle',
                'description' => 'Vehicle for user',
                'category_id' => 1,
                'user_id' => 3,
                'brand' => 'UserBrand',
                'model' => 'UserModel',
                'year' => 2022,
                'condition' => 'new',
                'price' => 5000,
                'currency' => 'EUR',
                'location' => 'User City',
                'contact_phone' => '0123456789',
                'contact_email' => 'uservehicle@test.com',
                'specifications' => json_encode([['key'=>'Fuel','value'=>'Diesel']]),
                'images' => json_encode(['vehicles/user1.png']),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
                'vat' => 19,
            ],
            [
                'id' => 2,
                'title' => 'Admin Vehicle 1',
                'description' => 'Vehicle for admin',
                'category_id' => 1,
                'user_id' => 2,
                'brand' => 'AdminBrand1',
                'model' => 'AdminModel1',
                'year' => 2021,
                'condition' => 'used',
                'price' => 7000,
                'currency' => 'EUR',
                'location' => 'Admin City',
                'contact_phone' => '0123456799',
                'contact_email' => 'adminvehicle1@example.com',
                'specifications' => json_encode([['key'=>'Fuel','value'=>'Petrol']]),
                'images' => json_encode(['vehicles/admin1.png']),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
                'vat' => 19,
            ],
            [
                'id' => 3,
                'title' => 'Admin Vehicle 2',
                'description' => 'Second vehicle for admin',
                'category_id' => 1,
                'user_id' => 2,
                'brand' => 'AdminBrand2',
                'model' => 'AdminModel2',
                'year' => 2020,
                'condition' => 'new',
                'price' => 9000,
                'currency' => 'EUR',
                'location' => 'Admin City',
                'contact_phone' => '0123456798',
                'contact_email' => 'adminvehicle2@example.com',
                'specifications' => json_encode([['key'=>'Fuel','value'=>'Electric']]),
                'images' => json_encode(['vehicles/admin2.png']),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
                'vat' => 19,
            ],
        ]);
    }
}
