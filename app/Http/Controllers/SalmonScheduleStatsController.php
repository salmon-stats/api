<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalmonScheduleStatsController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $scheduleId = \App\Helpers\Helper::scheduleIdToTimestamp($request->schedule_id);

        return DB::select(<<<'QUERY'
        WITH tide_event_fail_waves_aggregation AS (
            SELECT
                event_id,
                water_id,
                COUNT(*) AS waves,
                CASE WHEN clear_waves < 3 THEN 1 ELSE 0 END AS is_failed
                FROM salmon_results
                INNER JOIN salmon_waves ON
                    salmon_results.id = salmon_waves.salmon_id
                WHERE
                    schedule_id = '2019-09-26 12:00:00'
                GROUP BY event_id, water_id, is_failed
        )
        SELECT
            event_id,
            water_id,
            CONVERT(SUM(CASE WHEN is_failed = 0 THEN waves ELSE 0 END), unsigned) AS clear_waves,
            CONVERT(SUM(CASE WHEN is_failed = 1 THEN waves ELSE 0 END), unsigned) AS fail_waves
            FROM tide_event_fail_waves_aggregation
            GROUP BY event_id, water_id
        QUERY);
    }
}
