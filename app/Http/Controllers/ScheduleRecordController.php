<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScheduleRecordController extends Controller
{
    function __invoke(Request $request) {
        $scheduleId = $request->schedule_id;
        $scheduleTimestamp = \App\Helpers\Helper::scheduleIdToTimestamp($scheduleId);

        $queries = [
            ['golden_egg_delivered', 'golden_eggs'],
            ['power_egg_collected', 'power_eggs'],
        ];

        $response = [];

        try {
            foreach ($queries as $query) {
                $totalRecord = $this->buildTotalEggQuery($query[1], $scheduleTimestamp)->first();
                if (empty($totalRecord)) {
                    return null;
                }

                $noNightTotalRecord = $this->buildNoNightTotalEggQuery($query[1], $scheduleTimestamp)->first();
                $rawWaveRecords = DB::select($this->buildTideXEventRecordsQuery($query), [$scheduleTimestamp]);
                $waveRecords = array_map(
                    function ($wave) {
                        $wave->members = json_decode($wave->members);
                        return $wave;
                    },
                    $rawWaveRecords,
                );

                $response['totals'][$query[1]] = $totalRecord;
                $response['no_night_totals'][$query[1]] = empty($noNightTotalRecord) ? null : $noNightTotalRecord;
                $response['wave_records'][$query[1]] = $waveRecords;
            }
        }
        catch (\InvalidArgumentException $e) {
            abort(422, 'Invalid schedule id');
        }
        catch (Illuminate\Database\QueryException $e) {
            abort(500, 'Query error');
        }

        return $response;
    }

    static function buildTotalEggQuery($orderByColumn, $scheduleId) {
        return \App\SalmonResult::select('id', 'golden_egg_delivered AS golden_eggs', 'power_egg_collected AS power_eggs', 'members')
            ->where('schedule_id', $scheduleId)
            ->orderBy($orderByColumn, 'desc')
            ->orderBy('id');
    }

    static function buildNoNightTotalEggQuery(...$args) {
        return self::buildTotalEggQuery(...$args)
            ->where('is_eligible_for_no_night_record', 1);
    }

    static function buildTideXEventRecordsQuery($query) {
        return <<<QUERY
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
    SELECT salmon_water_levels.id AS water_id, salmon_events.id AS event_id
        FROM salmon_events
    CROSS JOIN salmon_water_levels
),
records AS (
    SELECT
            salmon_results.id AS id,
            wave_results.water_id,
            wave_results.event_id AS event_id,
            wave_results.golden_egg_delivered AS golden_eggs,
            wave_results.power_egg_collected AS power_eggs,
            ROW_NUMBER() OVER (PARTITION BY wave_results.water_id, wave_results.event_id
                ORDER BY wave_results.$query[0] DESC, id ASC) AS row_num,
            salmon_results.members
                FROM water_x_event
        INNER JOIN wave_results ON water_x_event.water_id = wave_results.water_id
            AND (water_x_event.event_id = wave_results.event_id
                OR (water_x_event.event_id IS NULL AND wave_results.event_id IS NULL))
        INNER JOIN salmon_results ON wave_results.salmon_id = salmon_results.id
)
SELECT id, water_id, event_id, golden_eggs, power_eggs, members
    FROM records
    WHERE row_num = 1
QUERY;
    }
}
