<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SalmonPlayerBossElimination extends Model
{
    use \Awobaz\Compoships\Compoships;

    protected $table = 'salmon_player_boss_eliminations';
    protected $hidden = ['salmon_id', 'player_id'];
    protected $casts = [
        'counts' => 'object',
    ];
}
