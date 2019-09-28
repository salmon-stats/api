<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\SalmonResult;
use App\SalmonPlayerResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalmonPlayerScheduleController extends Controller
{
    function index(Request $request)
    {
        return SalmonPlayerResult::select(DB::raw(<<<QUERY
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
            ->join('salmon_results', 'salmon_results.id', '=', 'salmon_player_results.salmon_id')
            ->join('salmon_schedules', 'salmon_schedules.schedule_id', '=', 'salmon_results.schedule_id')
            ->groupBy('salmon_results.schedule_id')
            ->orderBy('salmon_results.schedule_id', 'desc')
            ->paginate(10);
    }
}
