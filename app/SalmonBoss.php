<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SalmonBoss extends Model
{
    protected $table = 'salmon_bosses';
    protected $hidden = ['splatnet'];
}
