<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventStatusTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('event_status')->insert([
            [
                'id' => 1,
                'uid' => 'open',
                'text' => 'Open',
            ],
            [
                'id' => 2,
                'uid' => 'close',
                'text' => 'Close',
            ],
        ]);
    }
}
