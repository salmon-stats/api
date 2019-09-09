<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalmonPlayerMetadata extends Controller
{
    private const MAX_IDS_PER_REQUEST = 100;

    function __invoke(Request $request)
    {
        $ids = explode(',', $request->query('ids'));

        if (count($ids) > self::MAX_IDS_PER_REQUEST) {
            abort(400);
        }

        if (count($ids) === 1) {
            // TODO: cache
            $id = $ids[0];

            $metadata = DB::table('salmon_player_names')
                ->leftJoin('users', 'users.player_id', '=', 'salmon_player_names.player_id')
                ->select(
                    DB::raw('COALESCE(users.name, salmon_player_names.name) AS name'),
                    DB::raw('CASE WHEN users.name = null THEN FALSE ELSE TRUE END AS is_registered'),
                    'users.twitter_avatar',
                    'salmon_player_names.player_id AS player_id',
                )
                ->where('salmon_player_names.player_id', $id)
                ->get()
                ->toArray();

            $metadata[0]->total = DB::table('salmon_player_results')
                    ->select(
                        DB::raw('CONVERT(SUM(golden_eggs), UNSIGNED) as golden_eggs'),
                        DB::raw('CONVERT(SUM(power_eggs), UNSIGNED) as power_eggs'),
                        DB::raw('CONVERT(SUM(rescue), UNSIGNED) as rescue'),
                        DB::raw('CONVERT(SUM(death), UNSIGNED) as death'),
                        DB::raw('CONVERT(SUM(boss_elimination_count), UNSIGNED) as boss_elimination_count'),
                    )
                    ->where('player_id', $id)
                    ->first();

            $results = collect(DB::table('salmon_player_results')
                ->join('salmon_results', 'salmon_results.id', '=', 'salmon_player_results.salmon_id')
                ->select(
                    DB::raw('CASE WHEN fail_reason_id IS NULL THEN "clear" ELSE "fail" END as result'),
                    DB::raw('count(*) as count'),
                )
                ->where('player_id', $id)
                ->groupBy('result')
                ->get()
            );

            $clear = $results->firstWhere('result', 'clear');
            $fail = $results->firstWhere('result', 'fail');

            $metadata[0]->results = [
                'clear' => $clear ? $clear->count : 0,
                'fail' => $fail ? $fail->count : 0,
            ];

            return $metadata;
        }
        else {
            DB::table('salmon_player_names')
                ->select(
                    DB::raw('COALESCE(users.name, salmon_player_names.name) AS name'),
                    DB::raw('CASE WHEN users.name = null THEN FALSE ELSE TRUE END AS is_registered'),
                    'users.twitter_avatar',
                    'salmon_player_names.player_id AS player_id',
                )
                ->whereIn('salmon_player_names.player_id', $ids)
                ->leftJoin('users', 'users.player_id', '=', 'salmon_player_names.player_id')
                ->limit(self::MAX_IDS_PER_REQUEST)
                ->get();
        }
    }
}
