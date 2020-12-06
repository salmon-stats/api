<?php

namespace App\UseCases;

use App\Helpers\SalmonResultQueryHelper;
use App\Helpers\SalmonResultsFilterHelper;
use App\SalmonResult;
use App\SalmonPlayerResult;

class IndexResultUsecase
{
    const DEFAULT_RAW_RESULTS_COUNT = 100;
    const MAXIMUM_RAW_RESULTS_COUNT = 200;

    public function __invoke($playerId = null, $scheduleTimestamp = null, String $routeName = '', array $query = [])
    {
        if (isset($query['raw'])) {
            return $this->rawResults($playerId, isset($query['count']) ? $query['count'] : null);
        }

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

    private function rawResults(string $playerId, ?int $count)
    {
        if (!isset($playerId))  {
            abort(400);
        }

        $count = max(1, min($count ?? self::DEFAULT_RAW_RESULTS_COUNT, self::MAXIMUM_RAW_RESULTS_COUNT));

        $resultsWithPagination = SalmonResult::whereJsonContains('members', $playerId)
            ->orderBy('id', 'desc')
            ->paginate($count)
            ->toArray();
        $results = $resultsWithPagination['data'];
        unset($resultsWithPagination['data']);

        return array_merge(
            $resultsWithPagination,
            ['results' => SalmonResultQueryHelper::queryFullResults($results)],
        );
    }
}
