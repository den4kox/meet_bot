<?php

use Illuminate\Database\Seeder;

class QuestionsDefaultTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('questions_default')->insert([
            [ 'text' => 'Что ты делал вчера?' ],
            [ 'text' => 'Что ты планируешь делать сегодня?' ],
            [ 'text' => 'Что тебя блокирует?' ],
        ]);
    }
}
