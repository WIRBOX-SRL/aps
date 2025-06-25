<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Plan::create([
            'name' => 'Basic',
            'slug' => 'basic',
            'description' => 'Basic plan for small businesses',
            'price' => 1000, // $10.00
            'user_limit' => 5,
            'resources' => [
                'User' => [
                    'permissions' => ['view', 'create'],
                    'create_limit' => 5,
                ],
                'Vehicle' => [
                    'permissions' => ['view', 'create'],
                    'create_limit' => 10,
                ],
                'Announcement' => [
                    'permissions' => ['view', 'create'],
                    'create_limit' => 20,
                ],
                'Category' => [
                    'permissions' => ['view'],
                    'create_limit' => 0,
                ],
            ],
        ]);

        Plan::create([
            'name' => 'Premium',
            'slug' => 'premium',
            'description' => 'Premium plan for growing businesses',
            'price' => 2500, // $25.00
            'user_limit' => 20,
            'resources' => [
                'User' => [
                    'permissions' => ['view', 'create', 'edit'],
                    'create_limit' => 20,
                ],
                'Vehicle' => [
                    'permissions' => ['view', 'create', 'edit', 'delete'],
                    'create_limit' => 50,
                ],
                'Announcement' => [
                    'permissions' => ['view', 'create', 'edit', 'delete'],
                    'create_limit' => 100,
                ],
                'Category' => [
                    'permissions' => ['view', 'create', 'edit'],
                    'create_limit' => 10,
                ],
            ],
        ]);

        Plan::create([
            'name' => 'Enterprise',
            'slug' => 'enterprise',
            'description' => 'Enterprise plan for large organizations',
            'price' => 5000, // $50.00
            'user_limit' => 100,
            'resources' => [
                'User' => [
                    'permissions' => ['view', 'create', 'edit', 'delete'],
                    'create_limit' => 100,
                ],
                'Vehicle' => [
                    'permissions' => ['view', 'create', 'edit', 'delete'],
                    'create_limit' => 500,
                ],
                'Announcement' => [
                    'permissions' => ['view', 'create', 'edit', 'delete'],
                    'create_limit' => 1000,
                ],
                'Category' => [
                    'permissions' => ['view', 'create', 'edit', 'delete'],
                    'create_limit' => 50,
                ],
                'Plan' => [
                    'permissions' => ['view'],
                    'create_limit' => 0,
                ],
                'Subscription' => [
                    'permissions' => ['view'],
                    'create_limit' => 0,
                ],
            ],
        ]);
    }
}
