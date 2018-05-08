<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Users extends Model
{
    protected $table = 'users';
    
    protected $fillable = ['id', 'status', 'first_name', 'last_name'];

    public function answers()
    {
        return $this->hasMany('App\Answers', 'user_id', 'id');
    }

    public function roles()
    {
        return $this->belongsToMany('App\Roles', 'user_roles', 'user_id', 'role_id')
        ->as('groups')
        ->withPivot('group_id');
    }

    public function groups()
    {
        return $this->belongsToMany('App\Groups', 'user_groups', 'user_id', 'group_id')
        ->as('status')
        ->withPivot('status');
    }
}
