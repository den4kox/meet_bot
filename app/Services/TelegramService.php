<?php

namespace App\Services;

use App\UserGroups;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use GuzzleHttp\Client;
use App\Utils\TelegramUtils;
use App\General;
use App\Users;
use App\Events;
use App\Questions;
use App\Answers;
use App\Roles;
use App\Groups;
use App\QuestionsDefault;


class TelegramService
{
    public function __construct()
    {
        $this->dateFormat = 'd-m-Y';
        $this->days = ['Воскресенье', 'Понедельник', 'Вторник', 'Среда',
            'Четверг', 'Пятница', 'Суббота'];
        $this->utils = new TelegramUtils();

        $data = $this->utils->getGeneral();
        $this->token = @$data['access-token'];
        $this->host = @$data['telegram-host'];
        $this->url = $this->host.'bot'.$this->token.'/';

        $this->client = new Client( array( 'base_uri' => $this->url ) );    
    }

    public function inlineQuery($params) {
        $query = @$params['inline_query']['query'];
        $id = @$params['inline_query']['id'];
        $this->sendInline($id, json_encode(['One', 'Two']));

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
            case '/week':
                return $this->getWeekAnswers($data);
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
        $message .= "/week - Отчет за неделю".PHP_EOL;

        $this->sendMessage($data['chat']['id'], $message);
        return 'ok';
    }
    public function getWeekAnswers($data) {
        $chatId = $data['chat']['id'];

        $userIds = [];
        // print_r($values);
        foreach ($data['entities'] as $item) {
            if($item['type'] === 'mention') {
                $username = trim(mb_substr($data['text'], $item['offset'] + 1, $item['length']));
                $tempUser = Users::where('username', $username)->first();
                if($tempUser) {
                    array_push($userIds, $tempUser->id);
                }

                continue;
            }

            if($item['type'] === 'text_mention') {
                array_push($userIds, $item['user']['id']);
            }
        }

        $group = Groups::find($chatId);
        $start = Carbon::parse('last monday')->startOfDay();
        $stop = Carbon::parse('next friday')->endOfDay();
        $events = $group->events()->whereBetween('created_at', [
            $start,
            $stop,
        ])->get();
        if(count($userIds) > 0) {
            $users = Users::whereIn('id', $userIds)->get();
        } else {
            $users = $group->users;
        }

        $message = '*=====================*'.PHP_EOL;
        $message .= 'Отчет за текущую неделю: *'.$start->format($this->dateFormat).' - '.$stop->format($this->dateFormat).'*'.PHP_EOL;

        $message .= "\tОтчет для Пользователей:".PHP_EOL;
        $message .= '---------------------'.PHP_EOL;
        foreach ($users as $user) {
            $message .= "\t\t".$this->getLink($user).PHP_EOL;
        }
        $message .= '---------------------'.PHP_EOL;

        foreach ($events as $event) {
            $dayofweek = $this->days[date('w', strtotime($event->created_at))];
            $message .= '*Миттинг #'.$dayofweek."*".PHP_EOL;

            foreach($users as $user) {
                $message .= $this->getUserAnswerMessage($user, $event);
            }
            $message .= PHP_EOL;
        }
        $message .= '*=====================*'.PHP_EOL;
        $this->sendMessage($data['chat']['id'], $message);
        return 'ok';
    }

    function filter($item) {
        return $item['type'] === 'text_mention';
    }  

    public function getActiveUsers($data) {
        $group = Groups::find($data['chat']['id']);
        $users = $group->users()->where('status', 1)->get();
        $message = "Список участников миттинга:".PHP_EOL;
        $message .= "-------------".PHP_EOL;
        foreach($users as $user) {
            $role = Roles::find($user->info->role_id);

            print_r($user->toArray());
            $message .= $this->getLink($user)." *Роль:* ".$role->name.PHP_EOL;
        }
        $message .= "-------------".PHP_EOL;
        $this->sendMessage($data['chat']['id'], $message, 'Markdown');
        return 'ok';
    }

    public function deleteUser($data) {
        $user = Users::find($data['left_chat_participant']['id']);
        if(!empty($user)) {
            $fullName = $this->getLink($user);
            
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
        $dayofweek = $this->days[date('w', strtotime($lastEvent->created_at))];
        $message = '---------------------'.PHP_EOL;
        $message .= '*Миттинг #'.$dayofweek.'*'.PHP_EOL;

        foreach($users as $user) {
            $user = Users::find($user);
            $message .= $this->getUserAnswerMessage($user, $lastEvent);
        }
        $message .= '---------------------'.PHP_EOL;
        $this->sendMessage($data['chat']['id'], $message);
        return 'qwe';
    }

    public function getUserAnswerMessage(Users $user, Events $event) {
        $answers = $event->answers()->where('user_id', $user->id)->with('question')->get()->toArray();
        $userLink = $this->getLink($user);
        $message = "\t\t".$userLink.PHP_EOL;
        if(count($answers) === 0) {
            $message .= "\t\t\t*Ответы отсутствуют!*".PHP_EOL;
        }
        foreach($answers as $key => $answer) {
            $num = $key + 1;
            $message .= "\t\t".$num.".) *".$answer['question']['text']."*".PHP_EOL;
            $message .= "\t\t\t    _".$answer['text']."_".PHP_EOL;
        }

        return $message;
    }

    public function show($data) {
        $user = Users::find($data['from']['id']);
        if(empty($user)) {
            return '';
        }
        $lastEvent = Events::where('group_id', $data['chat']['id'])->orderBy('id', 'desc')->first();
        $dayofweek = $this->days[date('w', strtotime($lastEvent->created_at))];
        $answers = $lastEvent->answers()->where('user_id', $user->id)->with('question')->get()->toArray();
        $message = '---------------------'.PHP_EOL;
        $message .= '*Миттинг #'.$dayofweek."*".PHP_EOL;
        $message .= $this->getUserAnswerMessage($user, $lastEvent);
        $message .= '---------------------'.PHP_EOL;
        $this->sendMessage($data['chat']['id'], $message);
    }

    public function showQuestions($data) {
        $chatId = $data['chat']['id'];
        $questions = Questions::where('group_id', $chatId)->get();
        $message = 'Вопросы:'.PHP_EOL.PHP_EOL;
        $message .= '--------'.PHP_EOL;
        foreach($questions as $question) {
            $message .= "_".$question->text."_ (*".$question->id."*)".PHP_EOL;
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
                $this->sendMessage($chatId, "Вопрос c id *".$id_text[0]."* изменен!");
                return 'ok';
            }
            $this->sendMessage($chatId, "Вопрос c id *".$values[1]."* не найден!");
            return 'quetion not found';
        }
        $this->sendMessage($chatId, "Команда должна иметь вид: */command* _text_");
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
        $this->sendMessage($chatId, "Команда должна иметь вид: */command* _text_");
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
                $this->sendMessage($chatId, "Вопрос c id *".$values[1]."* удален!");
                return 'ok';
            }
            $this->sendMessage($chatId, "Вопрос c id *".$values[1]."* не найден!");
            return 'question not found';
        }
        $this->sendMessage($chatId, "Команда должна иметь вид: */command* _text_");
        return 'error';
    }

    public function addMe($user, $chat) {
        $newuser = Users::find($user['id']);
        $message = "";
        $username = $user['username'] ?? $user['id'];
        if(empty($newuser)) {
            Users::create(
                [ 'id' => @$user['id'], 'last_name' => @$user['last_name'], 'first_name' => @$user['first_name'], 'username' => @$username]
            );
            $newuser=Users::find($user['id']);
        }
        
        if($newuser->groups()->where('group_id', $chat['id'])->where('status', 1)->count() === 0) {
            $newuser->groups()->syncWithoutDetaching([$chat['id'] => ['status' => 1, 'role_id' => 3]]);
            $message = "Новый участник миттинга: ".$this->getLink($newuser).PHP_EOL;;
            $message .= "Напиши приватное сообщение(@shoxel\_meeting\_bot), чтобы я мог задавать тебе вопросы.";
            
        } else {
            $message = $this->getLink($newuser).", полегче! Ты уже участник митинга";
        }
        
        $this->sendMessage($chat['id'], $message);

        return $newuser;
    }

    public function kickMe($data, $chat) {
        $user = Users::find($data['id']);
        if($user) {
            $user->groups()->syncWithoutDetaching([$chat['id'] => ['status' => 0]]);
            $message = $this->getLink($user)." отказался от миттингов!";
            $this->sendMessage($chat['id'], $message);
        }
        return 'DELET';
    }

    public function stopMeeting($data) {
        $user = Users::find($data['from']['id']);
        if($this->chechIsModerator($user, $data['chat']['id'])) {
            $events = Events::where('group_id', $data['chat']['id'])->where('status_id', 1)->get();
            $chatId= $data['chat']['id'];
            if($events->count() > 0) {
                foreach($events as $event) {
                    $event->userActions()->delete();
                    $event->status_id = 2;
                    $event->save();
                }
                $message = $this->getLink($user).' окончил миттинг. Результат:';
                $this->sendMessage($chatId, $message);
                $this->showAll($data);
            } else {
                $message = 'Активных митингов нету!';
                $this->sendMessage($chatId, $message);
            }
            return 'ok';
        }
        
        $message = "У вас недостаточно прав!";
        $this->sendMessage($data['chat']['id'], $message); 
        
        return 'permission';
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
            $moder = $this->getLink($user);
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
        
        $qmessage = $question->text.' ('.$this->getGroupLink($group).')';
        $this->sendMessage($user->id, $qmessage);
        
    }

    public function chechIsModerator(Users $user, $groupId) {
        if($user) {
            $isAdmin = $user
                ->groups()
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

    public function sendMessage($chatId, $messahe, $parseMode='Markdown') {
        $resp = $this->client->post('sendMessage',
             [
                 'query' => [
                    'chat_id' => $chatId,
                    'text' => $messahe,
                    'parse_mode' => $parseMode
                 ]
            ] 
        );

        return $resp;
    }
    public function sendInline($queryId, $data) {
        $this->sendMessage('150401573', 'Inline!!!', 'HTML');
        $resp = $this->client->post('answerInlineQuery',
            [
                'query' => [
                    'inline_query_id' => $queryId,
                    'result' => $data,
                ]
            ]
        );

        return $resp;
    }

    public function getLink(Users $user) {
        return "[".$user->first_name." ".$user->last_name."](tg://user?id=".$user->id.")";
    }

    public function getGroupLink(Groups $group) {
        return "[".$group->name."](tg://group?id=".$group->id.")";
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
