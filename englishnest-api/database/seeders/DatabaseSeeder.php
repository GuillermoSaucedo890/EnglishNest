<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Este es el seeder principal.
     * Desde aquí llamamos a los demás seeders.
     */
    public function run(): void
    {
        $this->call([
            RolSeeder::class,
            AreaSeeder::class,
            PlanSeeder::class,
            AdminSeeder::class,
        ]);
    }
}