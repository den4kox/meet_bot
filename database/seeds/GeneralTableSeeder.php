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

        DB::table('general')->insert([
            [
                'label' => 'access-token',
                'value' => env('ACCESS_TOKEN', 'empty'),
            ],
            [
                'label' => 'telegram-host',
                'value' => 'https://api.telegram.org/',
            ],
        ]);
        $TS = new TelegramService();
        $TS->setHook('https://shoxel.com/telegram/handler/');
    }
}
