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

        $data = $this->utils->getGeneral();
        $this->token = @$data['access-token'];
        $this->host = @$data['telegram-host'];
        $this->url = $this->host.'bot'.$this->token.'/';

        $this->client = new Client( array( 'base_uri' => $this->url ) );    
    }

    public function commandHandler($data) {
        $cm = $this->utils->getTextFromCommand($data['text'], $data['entities'][0]['length'])[0];
        $command = explode("@", $cm)[0];
        switch ($command) {
            case '/addme':
                return $this->addMe($data['from'], $data['chat']);
            case '/kickme':
                return $this->kickMe($data['from'], $data['chat']);
            case '/startmeeting':
                return $this->startMeeting($data);
            case '/stopmeeting':
                return $this->stopMeeting($data);    
            case '/show':
                return $this->show($data);
            case '/showall':
                return $this->showAll($data);
            case '/showquestions':
                return $this->showQuestions($data);
            case '/editquestion':
                return $this->editQuestion($data);
            case '/addquestion':
                return $this->addQuestion($data);
            case '/deletequestion':
                return $this->deleteQuestion($data);
            case '/users':
                return $this->getActiveUsers($data);
            case '/start':
                return $this->start($data);                      
        }
        return $command;
    }
    // Commands
    public function start($data) {
        $message = "Список Доступных команд:".PHP_EOL;
        $message .= "/addme - Добавь меня".PHP_EOL;
        $message .= "/kickme - Удали меня".PHP_EOL;
        $message .= "/startmeeting - Начать миттинг".PHP_EOL;
        $message .= "/stopmeeting - Окончить миттинг".PHP_EOL;
        $message .= "/show - Последние ответы текущего пользователя".PHP_EOL;
        $message .= "/showall - Ответы всех".PHP_EOL;
        $message .= "/showquestions - Показать вопросы".PHP_EOL;
        $message .= "/addquestion - Добавить вопрос".PHP_EOL;
        $message .= "/editquestion - Редактировать вопрос".PHP_EOL;
        $message .= "/deletequestion - Удалить вопрос".PHP_EOL;
        $message .= "/users - Участники миттинга".PHP_EOL;
        $message .= "/start - Список команд".PHP_EOL;

        $this->sendMessage($data['chat']['id'], $message);
        return 'ok';
    }

    public function getActiveUsers($data) {
        $users = Users::where('status', 1)->get();
        $message = "Список участников миттинга:".PHP_EOL;
        $message .= "-------------".PHP_EOL;
        foreach($users as $user) {
            $message .= "  ".$user->first_name." ".$user->last_name.PHP_EOL;
        }
        $message .= "-------------".PHP_EOL;
        $this->sendMessage($data['chat']['id'], $message);
        return 'ok';
    }

    public function deleteUser($data) {
        $user = Users::find($data['left_chat_participant']['id']);
        if(!empty($user)) {
            $fullName = $user->first_name." ".$user->last_name;
            
            $message = "Пользователь ".$fullName." покинул нас...( Его кикнул ".$data['from']['first_name']." ".$data['from']['last_name'];
            $this->sendMessage($data['chat']['id'], $message);
            $user->status = 0;
            $user->save();
            return 'ok';
        }
        return 'user not found';
    }
    
    public function showAll($data) {
        $lastEvent = Events::orderBy('id', 'desc')->first();
        $users = $lastEvent->answers()->distinct('user_id')->pluck('user_id');

        $message = '*******************'.PHP_EOL;
        $message .= 'Миттинг #'.$lastEvent->id.'. Дата: '.$lastEvent->created_at.PHP_EOL;

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

    public function showQuestions($data) {
        $from = $data['chat']['id'];
        $questions = Questions::all();
        $message = 'Вопросы:'.PHP_EOL.PHP_EOL;
        $message .= '--------'.PHP_EOL;
        foreach($questions as $question) {
            $message .= "id=".$question->id.". Text: ".$question->text.PHP_EOL;
        }
        $message .= '--------'.PHP_EOL;
        $this->sendMessage($from, $message);
        return 'ok';
    }
    public function editQuestion($data) {
        $values = $this->utils->getTextFromCommand($data['text'], $data['entities'][0]['length']);
        $from = $data['chat']['id'];
        if (count($values) === 2) {
            $id_text = explode("_", $values[1]);
            $q = Questions::find($id_text[0]);
            if(!empty($q)) {
                $q->text = $id_text[1];
                $q->save();
                $this->sendMessage($from, "Вопрос c id ".$id_text[0]." изменен!");
                return 'ok';
            }
            $this->sendMessage($from, "Вопрос c id ".$values[1]." не найден!");
            return 'quetion not found';
        }
        $this->sendMessage($from, "Команда должна иметь вид: /command text");
        return 'error';
    }
    public function addQuestion($data) {
        $values = $this->utils->getTextFromCommand($data['text'], $data['entities'][0]['length']);
        $from = $data['chat']['id'];
        if (count($values) === 2 && strlen($values[1]) > 3) {
            $newQuestion = Questions::create([
                'text' => $values[1],
            ]);
            $this->sendMessage($from, "Вопрос Добавлен! /showquestions - чтобы посотреть все вопросы.");
            return 'ok';
        }
        $this->sendMessage($from, "Команда должна иметь вид: /command text");
        return 'error';
        
    }
    public function deleteQuestion($data) {
        $values = $this->utils->getTextFromCommand($data['text'], $data['entities'][0]['length']);
        $from = $data['chat']['id'];
        if (count($values) === 2) {
            $q = Questions::find($values[1]);
            if(!empty($q)) {
                $q->delete();
                $this->sendMessage($from, "Вопрос c id ".$values[1]." удален!");
                return 'ok';
            }
            $this->sendMessage($from, "Вопрос c id ".$values[1]." не найден!");
            return 'question not found';
        }
        $this->sendMessage($from, "Команда должна иметь вид: /command text");
        return 'error';
    }

    public function addMe($user, $chat) {
        $newuser = Users::find($user['id']);
        $message = "";
        print_r($newuser);
        if(empty($newuser)) {
            $newuser = Users::create(
                [ 'id' => $user['id'], 'last_name' => $user['last_name'], 'first_name' => $user['first_name'], 'status' => 1]
            );
            $message = "Новый участник миттинга: ".$newuser->first_name." ".$newuser->last_name.PHP_EOL;;
            $message .= "Напиши приватное сообщение(@shoxel_meeting_bot), чтобы я мог задавать тебе вопросы.";
            
        } else {
            if($newuser->status === 0) {
                $newuser->status = 1;
                $newuser->save();
                $message = "Новый участник миттинга: ".$newuser->first_name." ".$newuser->last_name.PHP_EOL;;
                $message .= "Напиши приватное сообщение(@shoxel_meeting_bot), чтобы я мог задавать тебе вопросы.";

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

    public function stopMeeting($data) {
        $events = Events::where('status_id', 1)->get();
        $from = $data['from'];
        $chatId= $data['chat']['id'];
        if($events->count() > 0) {
            foreach($events as $event) {
                $event->status_id = 2;
                $event->save();
            }
            $message = $from['first_name']." ".$from['last_name'].' окончил миттинг. Результат:';
            $this->sendMessage($chatId, $message);
            $this->showAll($data);
        } else {
            $message = 'Активных митингов нету!';
            $this->sendMessage($chatId, $message);
        }
        
        
        return 'ok';
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

    // utils
    public function sendMessage($chatId, $messahe) {
        $resp = $this->client->post('sendMessage',
             ['query' => ['chat_id' => $chatId,'text' => $messahe]] 
        );

        return $resp;
    }

    public function setHook($url) {
        $salt = str_random(5);
        $res = $this->client->post('setWebhook',
            ['query' => ['url' => $url.$salt]]
        );
        print_r($res->getStatusCode());
        if($res->getStatusCode() === 200) {
            General::updateOrCreate(
                ['label' => 'hook-url'],
                ['value'=> $url]
            ); 
            General::updateOrCreate(
                ['label' => 'hook-salt'],
                ['value'=> $salt]
            ); 
        }
        return $res->getBody();
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
