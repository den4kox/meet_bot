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


    public function handler(Request $request, $salt) {
        $params = $request->all();
        // $this->utils->setGeneralTable('last-update-id', $params['update_id']);
        //$this->utils->setGeneralTable('last-message-id', $params['message']['message_id']);
        //$allJson = json_encode($params);
        //$this->telegram->sendMessage('150401573', $allJson, 'HTML');

        if(@$params['inline_query']) {
            $res = $this->telegram->inlineQuery($params);
        }
        if(@$params['message']['new_chat_participant']['is_bot'] 
        && @$params['message']['new_chat_participant']['username'] === 'shoxel_meeting_bot') {
            $res = $this->telegram->botJoinGroup($params['message']);
            //$resp = $this->telegram->sendMessage('150401573', json_encode(['command' => $res]), 'HTML');
            return $res;
        }

        if(@$params['message']['left_chat_participant']['is_bot']
        && @$params['message']['left_chat_participant']['username'] === 'shoxel_meeting_bot') {
            $res = $this->telegram->deleteGroup($params['message']);
           // $resp = $this->telegram->sendMessage('150401573', json_encode(['command' => $res]), 'HTML');
            return $res;
        }

        if(@$params['message']['left_chat_participant']) {
            $res = $this->telegram->deleteUser($params['message']);
           // $resp = $this->telegram->sendMessage('150401573', json_encode(['command' => $res]), 'HTML');
            return $res;
        }

        
        if(@$params['message']['entities'][0]['type'] === 'bot_command') {
            $res = $this->telegram->commandHandler($params['message']);
            //$resp = $this->telegram->sendMessage('150401573', json_encode(['command' => $res]), 'HTML');
            return $res;
        }

        if(@$params['message']['chat']['type'] === 'private') {
            $res = $this->telegram->answerHandler($params['message']);
            //$resp = $this->telegram->sendMessage('150401573', json_encode(['command' => $res]), 'HTML');
            return $res;
        }
        
       // $statusCode = $resp->getStatusCode();
       // $body = $resp->getBody();
        return '';
    }
}
