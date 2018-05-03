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
            case '/addme':
                return $this->addMe($data['from'], $data['chat']);
            case '/kickme':
                return $this->kickMe($data['from'], $data['chat']);
            case '/startmeeting':
                return $this->startMeeting($data);
            case '/show':
                return $this->show($data);
            case '/showall':
                return $this->showAll($data);            
        }
    }

    public function showAll($data) {
        $lastEvent = Events::orderBy('id', 'desc')->first();
        $users = $lastEvent->answers()->distinct('user_id')->pluck('user_id');

        $message = '*******************'.PHP_EOL;
        $message .= 'Event #'.$lastEvent->id.'. Дата: '.$lastEvent->created_at.PHP_EOL;

        foreach($users as $user) {
            $user = Users::find($user);
            $message .= $this->getUserAnswerMessage($user, $lastEvent);
        }
        $message .= '*******************'.PHP_EOL;
        $this->sendMessage($data['chat']['id'], $message);
        return 'qwe';
    }

    public function getUserAnswerMessage(Users $user, Events $event) {
        $answers = $event->answers()->where('user_id', $user->id)->with('question')->get()->toArray();

        $message = '  '.$user->first_name.' '.$user->last_name.PHP_EOL;

        foreach($answers as $key => $answer) {
            print_r($answer);
            $num = $key + 1;
            $message .= "    ".$num.".) ".$answer['question']['text'].PHP_EOL;
            $message .= "    ".$answer['text'].PHP_EOL;
            $message .= PHP_EOL;
        }
        return $message;
    }

    public function show($data) {
        $user = Users::find($data['from']['id']);
        if(empty($user)) {
            return '';
        }

        $lastEvent = Events::orderBy('id', 'desc')->first();
        $answers = $lastEvent->answers()->where('user_id', $user->id)->with('question')->get()->toArray();
        $message = '*******************'.PHP_EOL;
        $message .= 'Event #'.$lastEvent->id.'. Дата: '.$lastEvent->created_at.PHP_EOL;
        $message .= '  '.$user->first_name.' '.$user->last_name.PHP_EOL;

        foreach($answers as $key => $answer) {
            print_r($answer);
            $num = $key + 1;
            $message .= "    ".$num.".) ".$answer['question']['text'].PHP_EOL;
            $message .= "    ".$answer['text'].PHP_EOL;
            $message .= PHP_EOL;
        }
        print_r($message);
        $message .= '*******************'.PHP_EOL;
        $this->sendMessage($data['chat']['id'], $message);
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

    public function addMe($user, $chat) {
        $newuser = Users::find($user['id']);
        $message = "";
        print_r($newuser);
        if(empty($newuser)) {
            print_r("QQQQ");
            $newuser = Users::create(
                [ 'id' => $user['id'], 'last_name' => $user['last_name'], 'first_name' => $user['first_name'], 'status' => 1]
            );
            $message = "Новый участник миттинга: ".$newuser->first_name." ".$newuser->last_name." Напиши мне в приват Привет!";
            
        } else {
            if($newuser->status === 0) {
                $newuser->status = 1;
                $newuser->save();
                $message = "Новый участник миттинга: ".$newuser->first_name." ".$newuser->last_name." Напиши мне в приват Привет!";

            } else {
                $message = $newuser->first_name." ".$newuser->last_name.", полегче! Ты уже участник митинга";
            }
                        
        }
        
        $this->sendMessage($chat['id'], $message);

        return $newuser;
    }

    public function kickMe($data, $chat) {
        $user = Users::find($data['id']);
        if($user) {
            $user->status = 0;
            $user->save();

            $message = $user->first_name." ".$user->last_name." отказался от миттингов!";
            $this->sendMessage($chat['id'], $message);
        }
        return 'DELET';
    }

    function startMeeting($data) {
        $lastEvent = Events::orderBy('id', 'desc')->where('status_id', 1)->first();
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
        $users = Users::where('status', 1)->get();
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
        if($user->status === 0) {
            $message = "Уважаемый! сначала /addMe, а потом миттинг";
            $this->sendMessage($user->id, $message);
            return '';
        }

        $event = Events::where('status_id', 1)->first();
        if(empty($event)) {
            $message = "Миттинг закончен. Для просмотра ваших последних ответов наберите команду /show(not work)";
            $this->sendMessage($userId, $message);
            return 'event not found';
        }
        $answers = Answers::where('user_id', $userId)->where('event_id', $event->id);
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
            $question = Questions::skip($countAnswers)->take(1)->first();
            $this->sendMessage($userId, $question->text);
            $answer = $user->answers()->create([
                'event_id' => $event->id,
                'question_id' => $question->id,
                'text' => 'Empty'
            ]);
        }
    }
}
