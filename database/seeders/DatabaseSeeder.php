<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\PlansTableSeeder;
use Database\Seeders\UsersTableSeeder;
use Database\Seeders\CitiesTableSeeder;
use Database\Seeders\StatesTableSeeder;
use Database\Seeders\SellersTableSeeder;
use Database\Seeders\VehiclesTableSeeder;
use Database\Seeders\CountriesTableSeeder;
use Database\Seeders\CategoriesTableSeeder;
use Database\Seeders\AnnouncementsTableSeeder;
use Database\Seeders\SubscriptionsTableSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            StatesTableSeeder::class,
            CitiesTableSeeder::class,
            CountriesTableSeeder::class,
            UsersTableSeeder::class,
            CategoriesTableSeeder::class,
            PlansTableSeeder::class,
            SubscriptionsTableSeeder::class,
            SellersTableSeeder::class,
            VehiclesTableSeeder::class,
            AnnouncementsTableSeeder::class,
            RolesAndPermissionsSeeder::class,
        ]);
    }
}
