<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScheduleRecordController extends Controller
{
    function __invoke(Request $request, string $scheduleId) {
        $scheduleTimestamp = \App\Helpers\Helper::scheduleIdToTimestamp($scheduleId);

        $queries = [
            ['golden_egg_delivered', 'golden_eggs'],
            ['power_egg_collected', 'power_eggs'],
        ];

        $response = [];

        try {
            foreach ($queries as $query) {
                $totalRecord = DB::select($this->buildTotalEggQuery($query), [$scheduleTimestamp]);

                if (sizeof($totalRecord) === 0) {
                    return response()->json(new \stdClass());
                }

                $response['totals'][$query[1]] = $totalRecord[0];
                $response['wave_records'][$query[1]] = DB::select($this->buildTideXEventRecordsQuery($query), [$scheduleTimestamp]);
            }
        }
        catch (\InvalidArgumentException $e) {
            abort(422, 'Invalid schedule id');
        }
        catch (Illuminate\Database\QueryException $e) {
            abort(500, 'Query error');
        }
        catch (\Exception $e) {
            abort(500, "Unhandled Exception: {$e->getMessage()}");
        }

        return $response;
    }

    static function buildTotalEggQuery($query) {
        $totalEggsQuery = <<<QUERY
SELECT id, $query[0] AS $query[1]
    FROM salmon_results
    WHERE schedule_id = ?
    ORDER BY $query[1] DESC
    LIMIT 1
QUERY;
        return $totalEggsQuery;
    }

    static function buildTideXEventRecordsQuery($query) {
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
            wave_results.event_id AS event_id,
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
}
