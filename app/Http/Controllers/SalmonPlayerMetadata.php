<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
            $playerId = $ids[0];

            return Cache::remember("players.metadata.$playerId", 86400, fn () => $this->getSinglePlayerMetadata($ids[0]));
        } else {
            return $this->getMultiplePlayersMetadata($ids);
        }
    }

    private function getSelectClause() {
        return [
            DB::raw('COALESCE(users.display_name, users.name, salmon_player_names.name) AS name'),
            DB::raw('CASE WHEN users.name IS NULL THEN FALSE ELSE TRUE END AS is_registered'),
            DB::raw('users.display_name IS NOT NULL AS is_custom_name'),
            DB::raw('CASE WHEN show_twitter_avatar = 1 THEN users.twitter_avatar ELSE NULL END AS twitter_avatar'),
            'salmon_player_names.player_id AS player_id',
        ];
    }

    private function getSinglePlayerMetadata(string $id) {
        $metadata = DB::table('salmon_player_names')
            ->leftJoin('user_accounts', 'user_accounts.player_id', '=', 'salmon_player_names.player_id')
            ->leftJoin('users', 'users.id', '=', 'user_accounts.user_id')
            ->select(...$this->getSelectClause())
            ->where('salmon_player_names.player_id', $id)
            ->get()
            ->toArray();

        if (empty($metadata)) {
            throw new NotFoundHttpException();
        }

        $metadata[0]->total = DB::table('salmon_player_results')
            ->select(
                DB::raw('CONVERT(SUM(golden_eggs), UNSIGNED) as golden_eggs'),
                DB::raw('CONVERT(SUM(power_eggs), UNSIGNED) as power_eggs'),
                DB::raw('CONVERT(SUM(rescue), UNSIGNED) as rescue'),
                DB::raw('CONVERT(SUM(death), UNSIGNED) as death'),
                DB::raw('CONVERT(SUM(boss_elimination_count), UNSIGNED) as boss_elimination_count'),
            )
            ->where('salmon_player_results.player_id', $id)
            ->first();

        $results = collect(DB::table('salmon_player_results')
            ->join('salmon_results', 'salmon_results.id', '=', 'salmon_player_results.salmon_id')
            ->select(
                DB::raw('CASE WHEN fail_reason_id IS NULL THEN "clear" ELSE "fail" END as result'),
                DB::raw('count(*) as count'),
            )
            ->where('salmon_player_results.player_id', $id)
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

    private function getMultiplePlayersMetadata(array $ids) {
        return DB::table('salmon_player_names')
            ->select(...$this->getSelectClause())
            ->whereIn('salmon_player_names.player_id', $ids)
            ->leftJoin('user_accounts', 'user_accounts.player_id', '=', 'salmon_player_names.player_id')
            ->leftJoin('users', 'users.id', '=', 'user_accounts.user_id')
            ->limit(self::MAX_IDS_PER_REQUEST)
            ->get();
    }
}
