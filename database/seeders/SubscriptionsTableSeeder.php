<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubscriptionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('subscriptions')->truncate();
        DB::table('subscriptions')->insert([
            [
                'id' => 1,
                'user_id' => 2, // admin
                'plan_id' => 1,
                'name' => 'full-access',
                'stripe_id' => null,
                'stripe_status' => 'active',
                'stripe_price' => null,
                'quantity' => 1,
                'trial_ends_at' => now()->addDays(14),
                'ends_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'subscription_type' => 'monthly',
            ],
        ]);
    }
}
