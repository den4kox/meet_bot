<?php

namespace App\Services;

use GuzzleHttp\Client;
use App\Utils\TelegramUtils;
use App\General;
use App\Users;
use App\Events;
use App\Questions;
use App\Answers;
class TelegramService
{
    public function __construct()
    {
        $this->utils = new TelegramUtils();

        $data = $this->utils->getGeneral(General::all());
        $this->token = @$data['access-token'];
        $this->host = @$data['telegram-host'];
        $this->url = $this->host.'bot'.$this->token.'/';

        $this->client = new Client( array( 'base_uri' => $this->url ) );    
    }
    public function commandHandler($data) {
    
        $command = explode("@", $data['text'])[0];
        switch ($command) {
            case '/attach':
                return $this->attach($data['from']);
            case '/deattach':
                return $this->deattach($data['from']);
            case '/startmeeting':
                return $this->startMeeting($data);    
        }
    }

    public function sendMessage($chatId, $messahe) {
        $resp = $this->client->post('sendMessage',
             [
            
                'query' => [
                    'chat_id' => $chatId, 
                    'text' => $messahe,
                ] 
            ] 
        );

        return $resp;
    }

    public function setHook($url) {
        $res = $this->client->post('setWebhook',
            [
                'query' => [
                    'url' => $url
                ]
            ]
        );
        print_r($res->getStatusCode());
        if($res->getStatusCode() === 200) {
            General::updateOrCreate(
                ['label' => 'hook-url'],
                ['value'=> $url]
            ); 
        }
        return $res->getBody();
    }

    public function deleteUser($data) {
        $user = Users::find($data['left_chat_participant']['id']);
        if(!empty($user)) {
            $fullName = $user->first_name." ".$user->last_name;

            
            $message = "Пользователь ".$fullName." покинул нас...( Его кикнул ".$data['from']['first_name']." ".$data['from']['last_name'];
            $this->sendMessage($data['chat']['id'], $message);
            $user->delete();
            return 'GOOD';
        }
        return 'NO';
    }

    public function attach($data) {
        $user = Users::firstOrCreate([
            'id' => $data['id'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
        ]);
        return $user;
    }

    public function deattach($data) {
        $user = Users::find($data['id']);
        if($user) {
            $user->delete();
        }
        return 'DELET';
    }


    public function addUser($data) {
        $userData = $data['new_chat_participant'];
        $user = $this->attach($userData);
        $message = "Добро пожаловать ".$user->first_name." ".$user->last_name.". Пригласил ".$data['from']['first_name']." ".$data['from']['last_name'];
        $this->sendMessage($data['chat']['id'], $message);

        return $user;
    }

    function startMeeting($data) {
        $lastEvent = Events::orderBy('id', 'desc')->first();
        if(!empty($lastEvent)) {
            $lastEvent->status_id = 2;
            $lastEvent->save();
        }
        
        $event = Events::create([
            'status_id' => 1,
        ]);
        $owner = $data['from']['last_name']." ".$data['from']['first_name'];
        $message = "$owner начал Миттинг! Смотри приват!";
        $this->sendMessage($data['chat']['id'], $message);        
        $users = Users::all();
        $question = Questions::first();
        foreach($users as $user) {
            $this->sendMessage($user->id, $question->text);
            
            $answers = $user->answers()->create([
                'event_id' => $event->id,
                'question_id' => $question->id,
                'text' => 'Empty'
            ]);
        }
    }

    public function answerHandler($data) {
        $response = $data['text'];
        $userId = $data['from']['id'];
        $user = Users::find($userId);


        $event = Events::where('status_id', 1)->first();
        if(empty($event)) {
            return 'event not found';
        }
        $answers = Answers::where('user_id', $userId)->where('event_id', $event->id);
        $questions = Questions::all();
        $countAnswers = $answers->count();
        $questionsCount = Questions::count();
        $currentAnswer = $answers->where('text', 'Empty')->first();
        if(!empty($currentAnswer)) {
            $currentAnswer->text = $response;
            $currentAnswer->save();
        }
        if($questionsCount === $countAnswers) {
            $message = "Спасибо. Вы ответили на все вопросы.";
            $this->sendMessage($userId, $message);
        } else {
            $question = $questions->forPage($countAnswers, 1)->first();
            $this->sendMessage($userId, $question->text);
            $answer = $user->answers()->create([
                'event_id' => $event->id,
                'question_id' => $question->id,
                'text' => 'Empty'
            ]);
        }
    }
}
