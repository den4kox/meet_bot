<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Services\TelegramService;
use App\General;
class GeneralTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $salt = str_random(5);
        DB::table('general')->insert([
            [
                'label' => 'access-token',
                'value' => env('ACCESS_TOKEN', 'empty'),
            ],
            [
                'label' => 'hook-url',
                'value' => 'https://shoxel.com/telegram/handler/',
            ],
            [
                'label' => 'telegram-host',
                'value' => 'https://api.telegram.org/',
            ],
            [
                'label' => 'hook-salt',
                'value' => $salt,
            ],
        ]);
        $url = General::where('label', 'hook-url')->first()->value.$salt.'/';
        $TS = new TelegramService();
        $TS->setHook($url);

    }
}
