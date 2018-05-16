<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Users extends Model
{
    protected $table = 'users';
    
    protected $fillable = ['id', 'status', 'first_name', 'last_name', 'username'];

    public function answers()
    {
        return $this->hasMany('App\Answers', 'user_id', 'id');
    }

    public function groups()
    {
        return $this->belongsToMany('App\Groups', 'user_groups', 'user_id', 'group_id')
        ->as('info')
        ->withPivot('status', 'role_id');
    }

    public function actions()
    {
        return $this->hasMany('App\UserActions', 'user_id', 'id');
    }
}
