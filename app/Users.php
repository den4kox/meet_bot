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
}
