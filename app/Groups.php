<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Groups extends Model
{
    protected $table = 'groups';
    public $timestamps = false;

    protected $fillable = ['name', 'id'];
}
