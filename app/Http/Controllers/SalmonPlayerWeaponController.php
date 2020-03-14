<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
        return \App\SalmonPlayerWeapon::select(
            'weapon_id',
            \DB::raw('COUNT(*) as count'),
        )
            ->where('player_id', $request->player_id)
            ->groupBy('weapon_id')
            ->orderBy('count', 'desc')
            ->orderBy('weapon_id')
            ->get();
    }
}
