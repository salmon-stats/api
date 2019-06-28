<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SalmonResult extends Model
{
    protected $table = 'salmon_results';
    protected $hidden = [];
    protected $casts = [
        'members' => 'array',
        'boss_appearances' => 'object',
    ];
    protected $guarded = [];
    protected $appends = ['member_accounts'];

    public function playerResults()
    {
        return $this
            ->hasMany('App\SalmonPlayerResult', 'salmon_id')
            ->with(['bossEliminations', 'specialUses', 'weapons']);
    }

    public function getMemberAccountsAttribute() {
        return collect($this->members)->map(function ($playerId) {
            return \App\User::where('player_id', $playerId)->first();
        });
    }

    public function schedule()
    {
        return $this
            ->hasOne('App\SalmonSchedule', 'schedule_id', 'schedule_id');
    }

    public function waves()
    {
        return $this
            ->hasMany('App\SalmonWave', 'salmon_id')
            ->with(['event', 'water'])
            ->orderBy('wave');
    }
}
