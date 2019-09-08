<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalmonPlayerMetadata extends Controller
{
    function __invoke(Request $request)
    {
        $ids = explode(',', $request->query('ids'));

        if (count($ids) > 100) {
            abort(400);
        }

        return DB::table('salmon_player_names')
            ->select(
                DB::raw('COALESCE(users.name, salmon_player_names.name) AS name'),
                DB::raw('CASE WHEN users.name = null THEN FALSE ELSE TRUE END AS is_registered'),
                'users.twitter_avatar',
                'salmon_player_names.player_id AS player_id',
            )
            ->whereIn('salmon_player_names.player_id', $ids)
            ->leftJoin('users', 'users.player_id', '=', 'salmon_player_names.player_id')
            ->get();
    }
}
