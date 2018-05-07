<?php

use Illuminate\Database\Seeder;

class RoleTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('role_types')->insert([
            [
                'priority' => 10,
                'name' => 'admin',
            ],
            [
                'priority' => 5,
                'name' => 'moderator',
            ],
            [
                'priority' => 1,
                'name' => 'member',
            ],
        ]);
    }
}
