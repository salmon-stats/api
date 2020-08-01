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
        'is_eligible_for_no_night_record' => 'boolean',
    ];
    protected $guarded = [];

    public function playerResults()
    {
        return $this
            ->hasMany('App\SalmonPlayerResult', 'salmon_id')
            ->with(['bossEliminations', 'specialUses', 'weapons']);
    }

    public function getMemberAccountsAttribute() {
        return collect($this->members)->map(function ($playerId) {
            // TODO: optimize query
            $user = \App\User::join('user_accounts', 'user_accounts.user_id', '=', 'users.id')
                ->where('player_id', $playerId)
                ->first();

            if (empty($user)) {
                return [
                    'player_id' => $playerId,
                    'name' => \App\SalmonPlayerName::where('player_id', $playerId)->first()->name,
                ];
            }

            return $user;
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
            // ->with(['event', 'water'])
            ->orderBy('wave');
    }
}
