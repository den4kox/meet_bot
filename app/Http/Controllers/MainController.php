<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use App\Services\TelegramService;
use App\Utils\TelegramUtils;
use App\General;
// https://api.telegram.org/bot528975393:AAGixyvKXmLFEDBcEBjeqXL3-WxPYq41RvQ/setWebhook
class MainController extends Controller
{
    public function __construct()
    {
        $this->telegram = new TelegramService();
        $this->utils = new TelegramUtils();
    }

    public function setHook(Request $request) {
        $res = $this->telegram->setHook($request->url);
        return $res;
    }

    public function setGeneralTable(Request $request) {
        return $this->utils->setGeneralTable($request->label, $request->value);
    }


    public function handler(Request $request) {
        // https://api.telegram.org/bot528975393:AAGixyvKXmLFEDBcEBjeqXL3-WxPYq41RvQ/sendMessage
        $params = $request->all();
        
        $allJson = json_encode($params);

        if(@$params['message']['left_chat_participant']) {
            $res = $this->telegram->deleteUser($params['message']);
            
            // return $res;
        }

        $resp = $this->client->post('sendMessage',
            array( 'query' => array( 'chat_id' => '-1001395709569', 'text' => $allJson ) )
        );
        // $statusCode = $resp->getStatusCode();
        // $body = $resp->getBody();

        // return response()->json(['status' => $statusCode, 'body' => $body]);
    }
}
