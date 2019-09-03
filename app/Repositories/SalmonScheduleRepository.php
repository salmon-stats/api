<?php

namespace App\Repositories;

use App\SalmonSchedule;

class SalmonScheduleRepository
{
    public function get(String $scheduleId)
    {
        $scheduleTimestamp = \App\Helpers\Helper::scheduleIdToTimestamp($scheduleId);

        return [
            'schedule' => SalmonSchedule::where('schedule_id', $scheduleTimestamp)->first(),
            'results' => app()->call('App\Http\Controllers\SalmonResultController@index')->items(),
            'records' => app()->call('App\Http\Controllers\ScheduleRecordController@__invoke'),
        ];
    }
}
