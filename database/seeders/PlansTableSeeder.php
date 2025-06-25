<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlansTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('plans')->truncate();
        DB::table('plans')->insert([
            [
                'id' => 1,
                'name' => 'Full Access',
                'slug' => 'full-access',
                'stripe_plan_id' => null,
                'price' => 1000,
                'description' => 'Plan with full access to all resources.',
                'user_limit' => 10,
                'created_at' => now(),
                'updated_at' => now(),
                'resources' => json_encode([
                    'User' => [
                        'permissions' => ['view', 'create', 'edit', 'delete'],
                        'create_limit' => 10,
                    ],
                    'Vehicle' => [
                        'permissions' => ['view', 'create', 'edit', 'delete'],
                        'create_limit' => 10,
                    ],
                    'Announcement' => [
                        'permissions' => ['view', 'create', 'edit', 'delete'],
                        'create_limit' => 10,
                    ],
                    'Seller' => [
                        'permissions' => ['view', 'create', 'edit', 'delete'],
                        'create_limit' => 10,
                    ],
                ]),
            ],
        ]);
    }
}
