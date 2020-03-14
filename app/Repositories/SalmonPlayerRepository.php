<?php

namespace App\Repositories;

use App\SalmonResult;
use App\User;

class SalmonPlayerRepository
{
    public function get(String $playerId)
    {
        // Player must have appeared in salmon_results at least once.
        if (!SalmonResult::whereJsonContains('members', $playerId)->exists()) {
            abort(404, "Player `$playerId` has no record.");
        }

        $user = User::where('player_id', $playerId)->first();
        $results = app()->call('App\Http\Controllers\SalmonResultController@index');
        $weapons = app()->call('App\Http\Controllers\SalmonPlayerWeaponController@__invoke');

        return [
            'user' => $user,
            'results' => $results->items(),
            'weapons' => $weapons,
        ];
    }
}
