<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// https://api.telegram.org/bot528975393:AAGixyvKXmLFEDBcEBjeqXL3-WxPYq41RvQ/setWebhook
class MainController extends Controller
{
    public function __construct()
    {
        $this->token = '528975393:AAGixyvKXmLFEDBcEBjeqXL3-WxPYq41RvQ';
        $this->url = 'https://api.telegram.org/bot528975393:AAGixyvKXmLFEDBcEBjeqXL3-WxPYq41RvQ/';
    }

    public function get(Request $request) {
        // https://api.telegram.org/bot528975393:AAGixyvKXmLFEDBcEBjeqXL3-WxPYq41RvQ/sendMessage

        $client = new GuzzleHttp\Client();
        $res = $client
            ->post('https://api.telegram.org/bot528975393:AAGixyvKXmLFEDBcEBjeqXL3-WxPYq41RvQ/sendMessage', 
                [
                    "chat_id" => "-1001395709569",
                    "text" => "Янка забиянка22"
                ]
            );
        return 'qwe';
    }

    public function post(Request $request) {
        // https://api.telegram.org/bot528975393:AAGixyvKXmLFEDBcEBjeqXL3-WxPYq41RvQ/sendMessage

        $client = new GuzzleHttp\Client();
        $res = $client
            ->post('https://api.telegram.org/bot528975393:AAGixyvKXmLFEDBcEBjeqXL3-WxPYq41RvQ/sendMessage', 
                [
                    "chat_id" => "-1001395709569",
                    "text" => "Янка забиянка33"
                ]
            );
        return 'qwe'; 
    }
}
