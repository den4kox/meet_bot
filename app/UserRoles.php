<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserRoles extends Model
{
    protected $table = 'user_roles';
    public $timestamps = false;

    protected $fillable = ['user_id', 'id', 'gpoup_id', 'role_id'];
}
