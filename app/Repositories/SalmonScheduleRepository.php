<?php

namespace App\Repositories;

use App\SalmonSchedule;

class SalmonScheduleRepository
{
    public function get(String $scheduleId, bool $isRequestingResults)
    {
        $scheduleTimestamp = \App\Helpers\Helper::scheduleIdToTimestamp($scheduleId);

        $schedule = SalmonSchedule::where('schedule_id', $scheduleTimestamp)->first();
        $results = app()->call('App\Http\Controllers\SalmonResultController@index');

        if ($isRequestingResults) {
            return [
                'schedule' => $schedule,
                'results' => $results,
            ];
        }
        else {
            return [
                'schedule' => $schedule,
                'results' => $results->items(),
                'records' => app()->call('App\Http\Controllers\ScheduleRecordController@__invoke'),
            ];
        }
    }
}
