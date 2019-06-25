<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SalmonPlayerSpecialUse extends Model
{
    use \Awobaz\Compoships\Compoships;

    protected $table = 'salmon_player_special_uses';
    protected $hidden = ['salmon_id', 'wave', 'player_id'];
    protected $casts = [
        'counts' => 'array',
    ];
}
