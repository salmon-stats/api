<?php

namespace App\Http\Controllers;

use App\SalmonSchedule;
use Illuminate\Http\Request;

class SalmonScheduleMetadata extends Controller
{
    function __invoke(Request $request)
    {
        return SalmonSchedule::where(
            'schedule_id',
            \App\Helpers\Helper::scheduleIdToTimestamp($request->schedule_id),
        )
            ->firstOrFail();
    }
}
