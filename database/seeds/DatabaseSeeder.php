<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            EventStatusTableSeeder::class,
            GeneralTableSeeder::class,
            RolesTableSeeder::class,
            QuestionsDefaultTableSeeder::class,
        ]);
    }
}
