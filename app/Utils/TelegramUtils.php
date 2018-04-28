<?php

namespace App\Utils;
use App\General;
class TelegramUtils
{
    
    public function test() {
        return 'TelegramUtils';
    }

    public function getGeneral($collect) {
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
}
