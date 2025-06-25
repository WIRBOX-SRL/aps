<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurrenciesTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('currencies')->truncate();
        DB::table('currencies')->insert([
            [
                'id' => 1,
                'name' => 'Euro',
                'code' => 'EUR',
                'symbol' => '€',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
