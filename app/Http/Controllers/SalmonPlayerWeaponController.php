<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SalmonPlayerWeaponController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $playerId = $request->player_id;

        return Cache::remember("players.weapons.$playerId", 86400, fn () => $this->queryPlayerWeapons($playerId));
    }

    private function queryPlayerWeapons($playerId)
    {
        return \App\SalmonPlayerWeapon::select(
            'weapon_id',
            \DB::raw('COUNT(*) as count'),
        )
            ->where('player_id', $playerId)
            ->groupBy('weapon_id')
            ->orderBy('count', 'desc')
            ->orderBy('weapon_id')
            ->get();
    }
}
