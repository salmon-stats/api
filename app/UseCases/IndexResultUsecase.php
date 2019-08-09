<?php

namespace App\UseCases;

use App\SalmonResult;

class IndexResultUsecase
{
    public function __invoke($playerId = null, $scheduleTimestamp = null)
    {
        $salmonResults = new SalmonResult();

        if (!is_null($playerId)) {
            return $salmonResults
                ->whereJsonContains('members', $playerId)
                ->orderBy('start_at', 'desc')
                ->paginate(10);
        }
        else if (!is_null($scheduleTimestamp)) {
            return $salmonResults
                ->where('schedule_id', $scheduleTimestamp)
                ->orderBy('id', 'desc')
                ->paginate(10);
        }
        else {
            return $salmonResults
                ->orderBy('id', 'desc')
                ->paginate(10);
        }
    }
}
