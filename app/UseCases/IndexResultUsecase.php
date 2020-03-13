<?php

namespace App\UseCases;

use App\SalmonResult;
use App\SalmonPlayerResult;

class IndexResultUsecase
{
    public function __invoke($playerId = null, $scheduleTimestamp = null, String $routeName, array $query = [])
    {
        $results = new SalmonResult();

        if (preg_match('/\.summary$/', $routeName)) {
            $perPage = 10;
        } else {
            $perPage = 20;
        }

        if (!is_null($playerId)) {
            $results = new SalmonPlayerResult();

            $results = $results
                ->with(['weapons'])
                ->select(
                    '*',
                    'salmon_results.boss_elimination_count as boss_elimination_count',
                    'salmon_player_results.boss_elimination_count as player_boss_elimination_count',
                )
                ->where('player_id', $playerId);

            if (!is_null($scheduleTimestamp)) {
                $results = $results->where('schedule_id', $scheduleTimestamp);
            }

            $results = $results
                ->join('salmon_results', 'salmon_results.id', '=', 'salmon_player_results.salmon_id')
                ->orderBy('salmon_results.start_at', 'desc');
        } else {
            if (!is_null($scheduleTimestamp)) {
                $results = $results->where('schedule_id', $scheduleTimestamp);
            }

            $results = $results->orderBy('id', 'desc');
        }

        if (isset($query['is_cleared'])) {
            $operator = $query['is_cleared'] === 'true' ? '=' : '<';
            $results = $results->where('clear_waves', $operator, 3);
        }
        if (isset($query['min_golden_egg'])) {
            $results = $results->where('golden_egg_delivered', '>', $query['min_golden_egg']);
        }
        if (isset($query['max_golden_egg'])) {
            $results = $results->where('golden_egg_delivered', '<', $query['max_golden_egg']);
        }
        if (isset($query['min_power_egg'])) {
            $results = $results->where('power_egg_collected', '>', $query['min_power_egg']);
        }
        if (isset($query['max_power_egg'])) {
            $results = $results->where('power_egg_collected', '<', $query['max_power_egg']);
        }

        return $results->paginate($perPage);
    }
}
