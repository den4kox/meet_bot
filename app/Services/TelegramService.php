<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use App\Utils\TelegramUtils;
use App\General;
use App\Users;
use App\Events;
use App\Questions;
use App\Answers;
use App\Groups;
use App\QuestionsDefault;
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
    // global
    public function botJoinGroup($data) {
        Groups::firstOrCreate([
            'id' => $data['chat']['id'],
            'name' => $data['chat']['title'],
        ]);
        $group = Groups::find($data['chat']['id']);
        $defaultQuestions = QuestionsDefault::get(['text'])->toArray();
        $group->questions()->createMany($defaultQuestions);
        
        $message = "Миттинг бот активирован!";
        $this->sendMessage($group->id, $message);

        return $group;
    }

    public function deleteGroup($data) {
        $group = Groups::find($data['chat']['id']);
        
        if($group) {
            $group->delete();
            return 'group delete!';
        }
        return 'group not found';
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
        $group = Groups::find($data['chat']['id']);
        $users = $group->users()->where('status', 1)->get();
        $message = "Список участников миттинга:".PHP_EOL;
        $message .= "-------------".PHP_EOL;
        foreach($users as $user) {
            $role = $user->roles()->first();
            $message .= "  ".$user->first_name." ".$user->last_name.". Роль: ".$role.PHP_EOL;
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
        $lastEvent = Events::where('group_id', $data['chat']['id'])->orderBy('id', 'desc')->first();
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

        $lastEvent = Events::where('group_id', $data['chat']['id'])->orderBy('id', 'desc')->first();
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
        $chatId = $data['chat']['id'];
        $questions = Questions::where('group_id', $chatId)->get();
        $message = 'Вопросы:'.PHP_EOL.PHP_EOL;
        $message .= '--------'.PHP_EOL;
        foreach($questions as $question) {
            $message .= "id=".$question->id.". Text: ".$question->text.PHP_EOL;
        }
        $message .= '--------'.PHP_EOL;
        $this->sendMessage($chatId, $message);
        return 'ok';
    }
    public function editQuestion($data) {
        $values = $this->utils->getTextFromCommand($data['text'], $data['entities'][0]['length']);
        $chatId = $data['chat']['id'];
        $user = Users::find($data['from']['id']);
        if(!$this->chechIsModerator($user, $chatId)) {
            $emessage = "У вас недостаточно прав";
            $this->sendMessage($chatId, $emessage);
            return 'permission denied';
        }
        if (count($values) === 2) {
            $id_text = explode("_", $values[1]);
            $q = Questions::where('group_id', $chatId)->where('id', $id_text[0])->first();
            if(!empty($q)) {
                print_r($q);
                $q->text = $id_text[1];
                $q->save();
                $this->sendMessage($chatId, "Вопрос c id ".$id_text[0]." изменен!");
                return 'ok';
            }
            $this->sendMessage($chatId, "Вопрос c id ".$values[1]." не найден!");
            return 'quetion not found';
        }
        $this->sendMessage($chatId, "Команда должна иметь вид: /command text");
        return 'error';
    }
    public function addQuestion($data) {
        $values = $this->utils->getTextFromCommand($data['text'], $data['entities'][0]['length']);
        $chatId = $data['chat']['id'];
        $user = Users::find($data['from']['id']);
        
        if(!$this->chechIsModerator($user, $chatId)) {
            $emessage = "У вас недостаточно прав";
            $this->sendMessage($chatId, $emessage);
            return 'permission denied';
        }
        if (count($values) === 2 && strlen($values[1]) > 3) {
            $newQuestion = Questions::create([
                'text' => $values[1],
                'group_id' => $chatId,
            ]);
            $this->sendMessage($chatId, "Вопрос Добавлен! /showquestions - чтобы посотреть все вопросы.");
            return 'ok';
        }
        $this->sendMessage($chatId, "Команда должна иметь вид: /command text");
        return 'error';
        
    }
    public function deleteQuestion($data) {
        $values = $this->utils->getTextFromCommand($data['text'], $data['entities'][0]['length']);
        $chatId = $data['chat']['id'];
        $user = Users::find($data['from']['id']);
        
        if(!$this->chechIsModerator($user, $chatId)) {
            $emessage = "У вас недостаточно прав";
            $this->sendMessage($chatId, $emessage);
            return 'permission denied';
        }
        if (count($values) === 2) {
            $q = Questions::where('group_id', $chatId)->where('id', $values[1])->first();
            if(!empty($q)) {
                $q->delete();
                $this->sendMessage($chatId, "Вопрос c id ".$values[1]." удален!");
                return 'ok';
            }
            $this->sendMessage($chatId, "Вопрос c id ".$values[1]." не найден!");
            return 'question not found';
        }
        $this->sendMessage($chatId, "Команда должна иметь вид: /command text");
        return 'error';
    }

    public function addMe($user, $chat) {
        $newuser = Users::find($user['id']);
        $message = "";
        if(empty($newuser)) {
            $newuser = Users::create(
                [ 'id' => $user['id'], 'last_name' => $user['last_name'], 'first_name' => $user['first_name']]
            );
            $newuser=Users::find($user['id']);
        }
        
        if($newuser->groups()->where('group_id', $chat['id'])->where('status', 1)->count() === 0) {
            $newuser->roles()->syncWithoutDetaching([3 => ['group_id' => $chat['id']]]);
            $newuser->groups()->syncWithoutDetaching([$chat['id'] => ['status' => 1]]);
            $message = "Новый участник миттинга: ".$newuser->first_name." ".$newuser->last_name.PHP_EOL;;
            $message .= "Напиши приватное сообщение(@shoxel_meeting_bot), чтобы я мог задавать тебе вопросы.";
            
        } else {
            $message = $newuser->first_name." ".$newuser->last_name.", полегче! Ты уже участник митинга";
        }
        
        $this->sendMessage($chat['id'], $message);

        return $newuser;
    }

    public function kickMe($data, $chat) {
        $user = Users::find($data['id']);
        if($user) {
            $user->groups()->syncWithoutDetaching([$chat['id'] => ['status' => 0]]);
            $message = $user->first_name." ".$user->last_name." отказался от миттингов!";
            $this->sendMessage($chat['id'], $message);
        }
        return 'DELET';
    }

    public function stopMeeting($data) {
        $events = Events::where('group_id', $data['chat']['id'])->where('status_id', 1)->get();
        $from = $data['from'];
        $chatId= $data['chat']['id'];
        if($events->count() > 0) {
            foreach($events as $event) {
                $event->userActions()->delete();
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
        $user = Users::find($data['from']['id']);
        if($this->chechIsModerator($user, $data['chat']['id'])) {
            $lastEvent = Events::where('group_id', $data['chat']['id'])->where('status_id', 1)->orderBy('id', 'desc')->first();
            if(!empty($lastEvent)) {
                $lastEvent->status_id = 2;
                $lastEvent->save();

                $lastEvent->userActions()->delete();
            }
            
            $event = Events::create([
                'status_id' => 1,
                'group_id' => $data['chat']['id'],
            ]);
            $moder = $data['from']['last_name']." ".$data['from']['first_name'];
            $message = "$moder начал Миттинг! Смотри приват!";
            $this->sendMessage($data['chat']['id'], $message);

            $group = Groups::find($data['chat']['id']);
            $users = $group->users()->where('status', 1)->get();
            $questions = Questions::where('group_id', $group->id)->pluck('id')->toArray();
            print_r($questions);
            foreach($users as $user) {
                $arrayActions = [];
                $countActive = $user->actions()->where('status', 1)->count();
                foreach($questions as $key => $q) {
                    array_push($arrayActions, [
                        'question_id' => $q,
                        'event_id' => $event->id,
                        'status' => $key === 0 && $countActive < 1 ? 1 : 0,
                    ]);
                }

                $user->actions()->createMany(
                    $arrayActions
                );

                if($countActive === 0) {
                    $this->sendUserQuestion($user);
                } else {
                    $this->sendMessage($user->id, "Ожидается ответ на предыдущий вопрос...");
                }
            }
        } else {
            $message = "У вас недостаточно прав!";
            $this->sendMessage($data['chat']['id'], $message); 
        }

    }

    public function answerHandler($data) {
        $response = $data['text'];
        $userId = $data['from']['id'];
        $user = Users::find($userId);

        $action = $user->actions()->where('status', 1)->first();
        $question = Questions::find($action->question_id);
        $event = Events::find($action->event_id);
        $group = $event->group;

        if(!$event || $event->status === 2) {
            $message = "Миттинг закончен.";
            $this->sendMessage($userId, $message);
            $user->actions()->where('event_id', $action->event_id)->delete();

            $action = $user->actions()->first();
            if($action) {
                $action->status = 1;
                $actions->save();
                $this->sendUserQuestion($user);

                return 'next question';
            } else {
                $message = "Вопросов больше нет!";
                $this->sendMessage($userId, $message);
                return 'event not found';
            }            
        }

        $currentAnswer = $user->answers()->create([
            'event_id' => $event->id,
            'question_id' => $question->id,
            'text' => $response,
        ]);
        
        $action->delete();
        $action = $user->actions()->first();
        if($action) {
            $action->status = 1;
            $action->save();
            $this->sendUserQuestion($user);

            return 'next question';
        } else {
            $message = "Спасибо. Вы ответили на все вопросы.";
            $this->sendMessage($userId, $message);
        }
    }

    // utils
    public function sendUserQuestion(Users $user) {
        $action = $user->actions()->where('status', 1)->first();

        $question = Questions::find($action->question_id);
        $event = Events::find($action->event_id);
        $group = $event->group;
        
        $qmessage = $question->text.' ('.$group->name.' '.$group->id.')';
        $this->sendMessage($user->id, $qmessage);
        
    }

    public function chechIsModerator(Users $user, $groupId) {
        if($user) {
            $isAdmin = $user
                ->roles()
                ->where('role_id', '<', 3)
                ->where('group_id', $groupId)
                ->count();
            print_r($isAdmin);
            if($isAdmin !== 0) {
               return true;
            }
            return false;
        }
        return false;
    }

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
}
