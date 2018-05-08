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
}
