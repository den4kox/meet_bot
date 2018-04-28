<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class General extends Model
{

    protected $table = 'general';

    public $timestamps = false;

    protected $fillable = ['label', 'value'];
}
