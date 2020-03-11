<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SalmonPlayerName extends Model
{
    protected $table = 'salmon_player_names';
    protected $hidden = [];
    protected $fillable = ['player_id', 'name'];
}
