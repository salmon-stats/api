<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\SalmonResult;
use App\SalmonPlayerResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalmonPlayerScheduleController extends Controller
{
    private function getPlayerSummary(Request $request)
    {
        $scheduleId = Helper::scheduleIdToTimestamp($request->schedule_id);

        $summaryQuery = <<<QUERY
        COUNT(*) AS games,
        CONVERT(SUM(CASE WHEN fail_reason_id IS NULL THEN 1 ELSE 0 END), UNSIGNED) AS clear_games,
        CONVERT(SUM(clear_waves), UNSIGNED) AS clear_waves,
        CONVERT(SUM(boss_appearance_count), UNSIGNED) AS boss_appearance_count,
        CONVERT(SUM(boss_elimination_count), UNSIGNED) AS boss_elimination_count,
        SUM(JSON_EXTRACT(boss_appearances, '$."3"')) AS 'boss_appearance_3',
        SUM(JSON_EXTRACT(boss_appearances, '$."6"')) AS 'boss_appearance_6',
        SUM(JSON_EXTRACT(boss_appearances, '$."9"')) AS 'boss_appearance_9',
        SUM(JSON_EXTRACT(boss_appearances, '$."12"')) AS 'boss_appearance_12',
        SUM(JSON_EXTRACT(boss_appearances, '$."13"')) AS 'boss_appearance_13',
        SUM(JSON_EXTRACT(boss_appearances, '$."14"')) AS 'boss_appearance_14',
        SUM(JSON_EXTRACT(boss_appearances, '$."15"')) AS 'boss_appearance_15',
        SUM(JSON_EXTRACT(boss_appearances, '$."16"')) AS 'boss_appearance_16',
        SUM(JSON_EXTRACT(boss_appearances, '$."21"')) AS 'boss_appearance_21'
        QUERY;

        $summary = SalmonResult::where('schedule_id', $scheduleId)
            ->whereJsonContains('members', $request->player_id)
            ->select(DB::raw($summaryQuery))
            ->where('salmon_results.golden_egg_delivered', '!=', 0)
            ->where('salmon_results.power_egg_collected', '!=', 0)
            ->first()
            ->toArray();

        $scoresQuery = <<<QUERY
        SELECT
            CASE WHEN salmon_player_results.player_id = ? THEN true ELSE false END AS is_myself,
            CONVERT(SUM(rescue), UNSIGNED) AS rescue,
            CONVERT(SUM(death), UNSIGNED) AS death,
            CONVERT(SUM(salmon_player_results.boss_elimination_count), UNSIGNED) AS boss_elimination_count,
            CONVERT(SUM(golden_eggs), UNSIGNED) AS golden_eggs,
            CONVERT(SUM(power_eggs), UNSIGNED) AS power_eggs,
            SUM(JSON_EXTRACT(salmon_player_boss_eliminations.counts, '$."3"')) AS 'boss_elimination_3',
            SUM(JSON_EXTRACT(salmon_player_boss_eliminations.counts, '$."6"')) AS 'boss_elimination_6',
            SUM(JSON_EXTRACT(salmon_player_boss_eliminations.counts, '$."9"')) AS 'boss_elimination_9',
            SUM(JSON_EXTRACT(salmon_player_boss_eliminations.counts, '$."12"')) AS 'boss_elimination_12',
            SUM(JSON_EXTRACT(salmon_player_boss_eliminations.counts, '$."13"')) AS 'boss_elimination_13',
            SUM(JSON_EXTRACT(salmon_player_boss_eliminations.counts, '$."14"')) AS 'boss_elimination_14',
            SUM(JSON_EXTRACT(salmon_player_boss_eliminations.counts, '$."15"')) AS 'boss_elimination_15',
            SUM(JSON_EXTRACT(salmon_player_boss_eliminations.counts, '$."16"')) AS 'boss_elimination_16',
            SUM(JSON_EXTRACT(salmon_player_boss_eliminations.counts, '$."21"')) AS 'boss_elimination_21'

            FROM salmon_results
            LEFT OUTER JOIN salmon_player_results ON
                salmon_player_results.salmon_id = salmon_results.id
            INNER JOIN salmon_player_boss_eliminations ON
                salmon_player_boss_eliminations.salmon_id = salmon_results.id AND
                salmon_player_boss_eliminations.player_id = salmon_player_results.player_id
            WHERE
                schedule_id = ? AND
                json_contains(members, ?) AND
                salmon_results.golden_egg_delivered != 0 AND
                salmon_results.power_egg_collected != 0
            GROUP BY is_myself
            ORDER BY is_myself ASC
        QUERY;

        $scores = collect(DB::select($scoresQuery, [
            $request->player_id,
            $scheduleId,
            "[\"$request->player_id\"]",
        ]))
            ->map(function ($_scores) {
                $isMyself = $_scores->is_myself === 1;
                $scores = get_object_vars($_scores);

                foreach ($scores as $key => $value) {
                    if ($key === 'is_myself') {
                        unset($scores[$key]);
                    }
                    else {
                        $newKey = ($isMyself ? 'player_' : 'others_') . $key;
                        $scores[$newKey] = $value;
                        unset($scores[$key]);
                    }
                }
                return $scores;
            })
            ->collapse()
            ->toArray();

        return array_merge(
            $summary,
            $scores,
        );
    }

    function index(Request $request)
    {
        $summariesCollection = SalmonPlayerResult::select(DB::raw(<<<QUERY
        COUNT(*) AS games,
        CONVERT(SUM(CASE WHEN fail_reason_id IS NULL THEN 1 ELSE 0 END), UNSIGNED) AS clear_games,
        CONVERT(SUM(clear_waves), UNSIGNED) AS clear_waves,
        CONVERT(SUM(salmon_player_results.boss_elimination_count), UNSIGNED) AS player_boss_elimination_count,
        CONVERT(SUM(rescue), UNSIGNED) AS rescue,
        CONVERT(SUM(death), UNSIGNED) AS death,
        CONVERT(SUM(golden_eggs), UNSIGNED) AS player_golden_eggs,
        CONVERT(SUM(power_eggs), UNSIGNED) AS player_power_eggs,
        CONVERT(SUM(salmon_results.boss_elimination_count), UNSIGNED) AS team_boss_elimination_count,
        CONVERT(SUM(golden_egg_delivered), UNSIGNED) AS team_golden_eggs,
        CONVERT(SUM(power_egg_collected), UNSIGNED) AS team_power_eggs,
        salmon_schedules.*
        QUERY))
            ->where('player_id', $request->player_id)
            ->where('salmon_results.golden_egg_delivered', '!=', 0)
            ->where('salmon_results.power_egg_collected', '!=', 0)
            ->join('salmon_results', 'salmon_results.id', '=', 'salmon_player_results.salmon_id')
            ->join('salmon_schedules', 'salmon_schedules.schedule_id', '=', 'salmon_results.schedule_id')
            ->groupBy('salmon_results.schedule_id')
            ->orderBy('salmon_results.schedule_id', 'desc')
            ->paginate(10);
        $summaries = $summariesCollection->toArray();

        $scheduleIds = $summariesCollection->map(fn ($summary) => $summary->schedule_id);
        $placeholders = implode(',', array_fill(0, $summariesCollection->count(), '?'));
        $specialUses = DB::select(
            DB::raw(<<<QUERY
                WITH cte AS (
                    SELECT schedule_id,
                        salmon_player_results.salmon_id,
                        special_id,
                        SUM(COUNT) AS count
                    FROM salmon_player_special_uses
                        INNER JOIN salmon_results ON salmon_results.id = salmon_player_special_uses.salmon_id
                        INNER JOIN salmon_player_results ON salmon_player_results.salmon_id = salmon_player_special_uses.salmon_id
                        AND salmon_player_results.player_id = salmon_player_special_uses.player_id
                    WHERE salmon_player_special_uses.player_id = ?
                        AND schedule_id IN ($placeholders)
                    GROUP BY salmon_player_results.special_id,
                        salmon_results.schedule_id,
                        salmon_player_results.salmon_id
                )
                SELECT schedule_id,
                    special_id,
                    SUM(`count`) AS count,
                    COUNT(*) AS games
                FROM cte
                GROUP BY schedule_id,
                    special_id
                ORDER BY games DESC, count DESC, special_id
            QUERY),
            [$request->player_id, ...$scheduleIds],
        );

        foreach ($specialUses as $specialUse) {
            $i = array_keys(array_filter($summaries['data'], fn ($summary) => $summary['schedule_id'] === $specialUse->schedule_id))[0];
            $summary = &$summaries['data'][$i];
            if (!array_key_exists('specials', $summary)) {
                $summary['specials'] = [];
            }
            $summary['specials'][] = [
                'count' => $specialUse->count,
                'games' => $specialUse->games,
                'special_id' => $specialUse->special_id,
            ];
        }

        return $summaries;
    }

    function show(Request $request)
    {
        $scheduleId = Helper::scheduleIdToTimestamp($request->schedule_id);

        $weaponsQuery = <<<QUERY
SELECT weapon_id, count(weapon_id) AS count
    FROM salmon_results
    LEFT OUTER JOIN salmon_player_weapons ON
        salmon_player_weapons.salmon_id = salmon_results.id AND
        salmon_player_weapons.player_id = :player_id
    WHERE
        schedule_id = :schedule_id AND
        json_contains(members, :json_player_id)
    GROUP BY weapon_id
    ORDER BY count DESC, weapon_id ASC
QUERY;

        $weapons = DB::select($weaponsQuery, [
            'player_id' => $request->player_id,
            'json_player_id' => "[\"$request->player_id\"]",
            'schedule_id' => $scheduleId,
        ]);

        $specialsQuery = <<<QUERY
        WITH cte AS (
            SELECT schedule_id,
                salmon_player_results.salmon_id,
                special_id,
                SUM(COUNT) AS count
            FROM salmon_player_special_uses
                INNER JOIN salmon_results ON salmon_results.id = salmon_player_special_uses.salmon_id
                INNER JOIN salmon_player_results ON salmon_player_results.salmon_id = salmon_player_special_uses.salmon_id
                AND salmon_player_results.player_id = salmon_player_special_uses.player_id
            WHERE salmon_player_special_uses.player_id = :player_id
                AND schedule_id = :schedule_id
            GROUP BY salmon_player_results.special_id,
                salmon_results.schedule_id,
                salmon_player_results.salmon_id
        )
        SELECT schedule_id,
            special_id,
            SUM(`count`) AS count,
            COUNT(*) AS games
        FROM cte
        GROUP BY schedule_id,
            special_id
        ORDER BY games DESC, count DESC, special_id
        QUERY;

        $specials = DB::select($specialsQuery, [
            'player_id' => $request->player_id,
            'schedule_id' => $scheduleId,
        ]);

        $globalTeamSummaryQuery = <<<QUERY
COUNT(*) AS games,
CONVERT(SUM(CASE WHEN fail_reason_id IS NULL THEN 1 ELSE 0 END), UNSIGNED) AS clear_games,
CONVERT(SUM(clear_waves), UNSIGNED) AS clear_waves,
CONVERT(SUM(boss_appearance_count), UNSIGNED) AS boss_appearance_count,
CONVERT(SUM(boss_elimination_count), UNSIGNED) AS boss_elimination_count,
CONVERT(SUM(golden_egg_delivered), UNSIGNED) AS golden_eggs,
CONVERT(SUM(power_egg_collected), UNSIGNED) AS power_eggs,
SUM(JSON_EXTRACT(boss_appearances, '$."3"')) AS 'boss_appearance_3',
SUM(JSON_EXTRACT(boss_appearances, '$."6"')) AS 'boss_appearance_6',
SUM(JSON_EXTRACT(boss_appearances, '$."9"')) AS 'boss_appearance_9',
SUM(JSON_EXTRACT(boss_appearances, '$."12"')) AS 'boss_appearance_12',
SUM(JSON_EXTRACT(boss_appearances, '$."13"')) AS 'boss_appearance_13',
SUM(JSON_EXTRACT(boss_appearances, '$."14"')) AS 'boss_appearance_14',
SUM(JSON_EXTRACT(boss_appearances, '$."15"')) AS 'boss_appearance_15',
SUM(JSON_EXTRACT(boss_appearances, '$."16"')) AS 'boss_appearance_16',
SUM(JSON_EXTRACT(boss_appearances, '$."21"')) AS 'boss_appearance_21'
QUERY;

        $globalTeamSummary = SalmonResult::where('schedule_id', $scheduleId)
            ->select(DB::raw($globalTeamSummaryQuery))
            ->where('salmon_results.golden_egg_delivered', '!=', 0)
            ->where('salmon_results.power_egg_collected', '!=', 0)
            ->get();

        $globalPlayerSummaryQuery = <<<QUERY
SELECT
    CONVERT(SUM(rescue), UNSIGNED) AS rescue,
    SUM(JSON_EXTRACT(salmon_player_boss_eliminations.counts, '$."3"')) AS 'boss_elimination_3',
    SUM(JSON_EXTRACT(salmon_player_boss_eliminations.counts, '$."6"')) AS 'boss_elimination_6',
    SUM(JSON_EXTRACT(salmon_player_boss_eliminations.counts, '$."9"')) AS 'boss_elimination_9',
    SUM(JSON_EXTRACT(salmon_player_boss_eliminations.counts, '$."12"')) AS 'boss_elimination_12',
    SUM(JSON_EXTRACT(salmon_player_boss_eliminations.counts, '$."13"')) AS 'boss_elimination_13',
    SUM(JSON_EXTRACT(salmon_player_boss_eliminations.counts, '$."14"')) AS 'boss_elimination_14',
    SUM(JSON_EXTRACT(salmon_player_boss_eliminations.counts, '$."15"')) AS 'boss_elimination_15',
    SUM(JSON_EXTRACT(salmon_player_boss_eliminations.counts, '$."16"')) AS 'boss_elimination_16',
    SUM(JSON_EXTRACT(salmon_player_boss_eliminations.counts, '$."21"')) AS 'boss_elimination_21'

    FROM salmon_results
    LEFT OUTER JOIN salmon_player_results ON
        salmon_player_results.salmon_id = salmon_results.id
    INNER JOIN salmon_player_boss_eliminations ON
        salmon_player_boss_eliminations.salmon_id = salmon_results.id AND
        salmon_player_boss_eliminations.player_id = salmon_player_results.player_id
    WHERE schedule_id = ? AND
        salmon_results.golden_egg_delivered != 0 AND
        salmon_results.power_egg_collected != 0
QUERY;

        $globalPlayerSummary = DB::select($globalPlayerSummaryQuery, [
            $scheduleId,
        ]);

        $response = [
            'global' => array_merge(
                $globalTeamSummary[0]->toArray(),
                get_object_vars($globalPlayerSummary[0]),
            ),
        ];

        if (count($weapons) === 0) {
            return $response;
        }

        $results = app()->call('App\Http\Controllers\SalmonResultController@index');

        $personal_stats = [
            'specials' => $specials,
            'summary' => $this->getPlayerSummary($request),
            'weapons' => $weapons,
            'results' => $results->items(),
        ];

        return array_merge($response, $personal_stats);
    }
}
