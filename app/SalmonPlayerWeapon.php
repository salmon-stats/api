<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SalmonPlayerWeapon extends Model
{
    use \Awobaz\Compoships\Compoships;

    protected $table = 'salmon_player_weapons';
    protected $hidden = ['salmon_id', 'player_id', 'wave'];
    protected $guarded = [];
    public $timestamps = false;
}
