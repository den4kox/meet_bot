<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Questions extends Model
{
    protected $table = 'questions';
    public $timestamps = false;

    protected $fillable = ['id', 'text', 'group_id'];
}
