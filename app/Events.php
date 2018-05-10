<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Events extends Model
{
    protected $table = 'events';

    protected $fillable = ['id', 'status_id', 'group_id'];

    public function answers()
    {
        return $this->hasMany('App\Answers', 'event_id', 'id');
    }

    public function group() {
        return $this->belongsTo('App\Groups', 'group_id');
    }

    public function userActions() {
        return $this->hasMany('App\UserActions', 'event_id');
    }

    
}
