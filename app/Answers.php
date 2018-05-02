<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Answers extends Model
{
    protected $table = 'answers';
    protected $fillable = ['event_id', 'user_id', 'question_id', 'text'];
}
