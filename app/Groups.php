<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Groups extends Model
{
    protected $table = 'groups';
    public $timestamps = false;

    protected $fillable = ['name', 'id'];

    public function questions()
    {
        return $this->hasMany('App\Questions', 'group_id', 'id');
    }

    public function events() {
        return $this->hasMany('App\Events', 'group_id', 'id');
    }

    public function users() {
        return $this->belongsToMany('App\Users', 'user_groups', 'group_id', 'user_id')
        ->as('info')
        ->withPivot('status', 'role_id');
    }
}
