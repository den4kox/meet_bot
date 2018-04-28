<?php

namespace App\Services;

use App\Utils\TelegramUtils;
class TelegramService
{
    public function __construct()
    {
        $this->utils = new TelegramUtils();
        // $this->url = 'https://api.telegram.org/bot528975393:AAGixyvKXmLFEDBcEBjeqXL3-WxPYq41RvQ/';

        // $apiKey = '528975393:AAGixyvKXmLFEDBcEBjeqXL3-WxPYq41RvQ'; // Put your bot's API key here
        // $apiURL = 'https://api.telegram.org/bot' . $apiKey . '/';

        // $this->client = new Client( array( 'base_uri' => $apiURL ) );
    }

    public function test() {
        return 'TelegramService'.' '.$this->utils->test();
    }
}
