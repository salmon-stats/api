<?php

namespace App\UseCases;

use App\Helpers\SalmonResultsFilterHelper;
use App\SalmonResult;
use App\SalmonPlayerResult;

class IndexResultUsecase
{
    public function __invoke($playerId = null, $scheduleTimestamp = null, String $routeName, array $query = [])
    {
        $results = new SalmonResult();
        $orderByArgs = [];

        if (preg_match('/\.summary$/', $routeName)) {
            $perPage = 10;
        } else {
            $perPage = 20;
        }

        if (!is_null($playerId)) {
            $results = new SalmonPlayerResult();

            $results = $results
                ->with(['weapons'])
                ->distinct('salmon_results.id')
                ->select(
                    'salmon_player_results.*',
                    'salmon_results.*',
                    'salmon_results.boss_elimination_count as boss_elimination_count',
                    'salmon_player_results.boss_elimination_count as player_boss_elimination_count',
                )
                ->where('salmon_player_results.player_id', $playerId);

            if (!is_null($scheduleTimestamp)) {
                $results = $results->where('schedule_id', $scheduleTimestamp);
            }

            $results = $results
                ->join('salmon_results', 'salmon_results.id', '=', 'salmon_player_results.salmon_id');

            $orderByArgs = ['salmon_results.start_at', 'desc'];
        } else {
            if (!is_null($scheduleTimestamp)) {
                $results = $results->where('schedule_id', $scheduleTimestamp);
            }

            $orderByArgs = ['id', 'desc'];
        }

        $results = SalmonResultsFilterHelper::apply($results, $query, $orderByArgs);

        if (!empty($orderByArgs)) {
            $results = $results->orderBy(...$orderByArgs);
        }

        return $results->paginate($perPage);
    }
}
