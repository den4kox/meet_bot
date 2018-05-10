<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserActions extends Model
{
    protected $table = 'user_actions';

    protected $fillable = ['user_id', 'event_id', 'question_id'];
}
