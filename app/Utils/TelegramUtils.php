<?php

namespace App\Utils;
use App\General;
class TelegramUtils
{
    
    public function test() {
        return 'TelegramUtils';
    }

    public function getGeneral() {
        $collect = General::all();
        $total = $collect->reduce(function ($acc, $item) {
            $acc[$item['label']] = $item['value'];
            return $acc;
        }, []);

        return $total;
    }

    public function setGeneralTable($label, $value) {
        $res = General::updateOrCreate(
            ['label' => $label],
            ['value'=> $value]
        ); 
        return $res;
    }

    public function getTextFromCommand($command, $index) {
        $text = trim(mb_substr($command, $index));   
        $command = mb_substr($command, 0, $index);
        return [$command, $text];
    }

    public function checkSalt($salt) {
        $curSalt = General::where('label', 'hook-salt')->first();
        if($curSalt) {
            return $curSalt->value === $salt;
        }
        return false;
    }
}
