<?php

namespace App;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

class SalmonPlayerResult extends Model
{
    use \Awobaz\Compoships\Compoships;

    protected $table = 'salmon_player_results';
    protected $hidden = [];

    public function bossEliminations()
    {
        return $this
            ->hasOne(
                'App\SalmonPlayerBossElimination',
                ['salmon_id', 'player_id'],
                ['salmon_id', 'player_id']
            );
    }

    public function specialUses()
    {
        return $this
            ->hasMany(
                'App\SalmonPlayerSpecialUse',
                ['salmon_id', 'player_id'],
                ['salmon_id', 'player_id']
            )
            ->orderBy('wave');
    }

    public function weapons()
    {
        return $this
            ->hasMany(
                'App\SalmonPlayerWeapon',
                ['salmon_id', 'player_id'],
                ['salmon_id', 'player_id']
            )
            ->orderBy('wave');
    }
}
