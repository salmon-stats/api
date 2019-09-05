<?php

namespace App\UseCases;

use App\SalmonResult;
use App\SalmonPlayerResult;

class IndexResultUsecase
{
    public function __invoke($playerId = null, $scheduleTimestamp = null)
    {
        $salmonResults = new SalmonResult();

        if (!is_null($playerId)) {
            $salmonPlayerResults = new SalmonPlayerResult();
            return $salmonPlayerResults
                ->where('player_id', $playerId)
                ->join('salmon_results', 'salmon_results.id', '=', 'salmon_player_results.salmon_id')
                ->orderBy('salmon_results.start_at', 'desc')
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
