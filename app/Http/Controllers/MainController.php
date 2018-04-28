<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use App\Services\TelegramService;
// https://api.telegram.org/bot528975393:AAGixyvKXmLFEDBcEBjeqXL3-WxPYq41RvQ/setWebhook
class MainController extends Controller
{
    public function __construct()
    {
        $this->token = '528975393:AAGixyvKXmLFEDBcEBjeqXL3-WxPYq41RvQ';
        $this->url = 'https://api.telegram.org/bot528975393:AAGixyvKXmLFEDBcEBjeqXL3-WxPYq41RvQ/';

        $apiKey = '528975393:AAGixyvKXmLFEDBcEBjeqXL3-WxPYq41RvQ'; // Put your bot's API key here
        $apiURL = 'https://api.telegram.org/bot' . $apiKey . '/';

        $this->client = new Client( array( 'base_uri' => $apiURL ) );
    }

    public function handler(Request $request) {
        // https://api.telegram.org/bot528975393:AAGixyvKXmLFEDBcEBjeqXL3-WxPYq41RvQ/sendMessage
        $params = $request->all();
        
        $allJson = json_encode($params);

        if(@$params['message']['left_chat_member']) {
            $lol = TelegramService::test();

            return 'Ok'.' '.$lol;
        }

        $resp = $this->client->post('sendMessage',
            array( 'query' => array( 'chat_id' => '-1001395709569', 'text' => $allJson ) )
        );
        $statusCode = $resp->getStatusCode();
        $body = $resp->getBody();

        return response()->json(['status' => $statusCode, 'body' => $body]);
    }

    public function post(Request $request) {
        // https://api.telegram.org/bot528975393:AAGixyvKXmLFEDBcEBjeqXL3-WxPYq41RvQ/sendMessage
        $resp = $this->client->post('sendMessage',
            array( 'query' => array( 'chat_id' => '-1001395709569', 'text' => "Янка забиянка2" ) )
        );

        return 'gooo';
    }
    public function qwe(Request $request) {
        // https://api.telegram.org/bot528975393:AAGixyvKXmLFEDBcEBjeqXL3-WxPYq41RvQ/sendMessage

        return 'Rgooo';
    }
}
