<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserGroups extends Model
{
    protected $table = 'user_groups';
    public $timestamps = false;

    protected $fillable = ['user_id', 'id', 'group_id', 'status', 'role_id'];

    public function role()
    {
        return $this->belongsTo('App\Roles', 'role_id');
    }
}
