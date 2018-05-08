<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class QuestionsDefault extends Model
{
    protected $table = 'questions_default';
    public $timestamps = false;

    protected $fillable = ['text', 'id'];
}
