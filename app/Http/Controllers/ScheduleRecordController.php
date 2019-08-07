<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScheduleRecordController extends Controller
{
    function __invoke(Request $request, string $scheduleId) {
        if (preg_match('/^\\d{4}-\\d{2}-\\d{2} \\d{2} \\d{2}$/', $scheduleId)) {
            abort(422, 'Invalid schedule id');
        }

        $queries = [
            ['golden_egg_delivered', 'golden_eggs'],
            ['power_egg_collected', 'power_eggs'],
        ];

        function buildTotalEggQuery($query) {
            $totalEggsQuery = <<<QUERY
WITH salmon_ids AS (
    SELECT id FROM salmon_results
    WHERE schedule_id = ?
),
total_golden_eggs AS (
    SELECT salmon_id, CAST(SUM($query[0]) AS SIGNED) AS $query[1]
    FROM salmon_ids
    LEFT OUTER JOIN salmon_waves ON salmon_waves.salmon_id = salmon_ids.id
    GROUP BY salmon_id
)
SELECT salmon_id AS id, $query[1]
    FROM total_golden_eggs
    ORDER BY $query[1] DESC
    LIMIT 1
QUERY;
            return $totalEggsQuery;
        }

        function buildTideXEventRecordsQuery($query) {
            $queryBuilt = <<<QUERY
WITH salmon_ids AS (
    SELECT id FROM salmon_results
    WHERE schedule_id = ?
),
events_with_null AS (
    SELECT id FROM salmon_water_levels UNION SELECT NULL
),
wave_results AS (
    SELECT * FROM salmon_ids
        INNER JOIN salmon_waves ON salmon_ids.id = salmon_waves.salmon_id
),
water_x_event AS (
    SELECT water_level_ids.id AS water_id, salmon_events.id AS event_id
        FROM salmon_events
    CROSS JOIN
    (SELECT salmon_water_levels.id FROM salmon_water_levels) AS water_level_ids
),
records AS (
    SELECT
            id,
            wave_results.water_id,
            CASE WHEN wave_results.event_id IS NULL THEN 0 ELSE wave_results.event_id END AS event_id,
            $query[0] AS $query[1],
            ROW_NUMBER() OVER (PARTITION BY wave_results.water_id, wave_results.event_id ORDER BY $query[0] DESC) AS row_num
        FROM water_x_event
        INNER JOIN wave_results ON water_x_event.water_id = wave_results.water_id
            AND (water_x_event.event_id = wave_results.event_id
                OR (water_x_event.event_id IS NULL AND wave_results.event_id IS NULL))
)
SELECT id, water_id, event_id, $query[1] FROM records WHERE row_num = 1
QUERY;
            return $queryBuilt;
        }

        $response = [];

        try {
            foreach ($queries as $query) {
                $response['totals'][$query[1]] = DB::select(buildTotalEggQuery($query), [$scheduleId])[0];
                $response['wave_records'][$query[1]] = DB::select(buildTideXEventRecordsQuery($query), [$scheduleId]);
            }
        }
        catch (Illuminate\Database\QueryException $e) {
            abort(422, 'Invalid schedule id');
        }
        catch (\Exception $e) {
            abort(500, "Unhandled Exception: {$e->getMessage()}");
        }

        return $response;
    }
}