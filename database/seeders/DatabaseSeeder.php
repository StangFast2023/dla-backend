<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $this->call([
            ProvinceSeeder::class,
            PrefixSeeder::class,
            PositionSeeder::class,
            TypePositionSeeder::class,
            UpdateListDla::class,
            CallRound1Seeder::class,
            CallRound2Seeder::class,
            CallRound3Seeder::class,
        ]);
    }
}
