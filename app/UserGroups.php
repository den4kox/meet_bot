<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserGroups extends Model
{
    protected $table = 'user_groups';
    public $timestamps = false;

    protected $fillable = ['user_id', 'id', 'gpoup_id'];
}
