<?php

namespace App\UseCases;

use App\Helpers\SalmonResultsFilterHelper;
use App\SalmonPlayerBossElimination;
use App\SalmonResult;
use App\SalmonPlayerResult;
use App\SalmonPlayerSpecialUse;
use App\SalmonPlayerWeapon;
use App\SalmonWave;

class IndexResultUsecase
{
    const DEFAULT_RAW_RESULTS_COUNT = 100;
    const MAXIMUM_RAW_RESULTS_COUNT = 200;

    public function __invoke($playerId = null, $scheduleTimestamp = null, String $routeName, array $query = [])
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
            ->simplePaginate($count)
            ->toArray();
        $results = $resultsWithPagination['data'];
        unset($resultsWithPagination['data']);

        $ids = array_map(
            fn ($result) => $result['id'],
            $results,
        );

        $playerBossEliminations = SalmonPlayerBossElimination::whereIn('salmon_id', $ids)->get()->makeVisible(['salmon_id', 'player_id'])->toArray();
        $playerResults = SalmonPlayerResult::whereIn('salmon_id', $ids)->get()->makeVisible('salmon_id')->toArray();
        $playerWeapons = SalmonPlayerWeapon::whereIn('salmon_id', $ids)->get()->makeVisible(['salmon_id', 'player_id'])->toArray();
        $waveResults = SalmonWave::whereIn('salmon_id', $ids)->get()->makeVisible('salmon_id')->toArray();
        $specialUses = SalmonPlayerSpecialUse::whereIn('salmon_id', $ids)->get()->makeVisible(['salmon_id', 'player_id'])->toArray();

        $results = array_map(
            function ($salmonResult) use ($playerBossEliminations, $playerResults, $playerWeapons, $waveResults, $specialUses) {
                $playerResult = $this->filterRelevantResults($salmonResult['id'], $playerResults);
                $waveResult = $this->filterRelevantResults($salmonResult['id'], $waveResults);

                foreach ($playerResult as &$player) {
                    $player = array_merge(
                        $player,
                        [
                            'boss_eliminations' => $this->filterRelevantResults($salmonResult['id'], $playerBossEliminations, $player['player_id'])[0],
                            'weapons' => $this->filterRelevantResults($salmonResult['id'], $playerWeapons, $player['player_id']),
                            'special_uses' => $this->filterRelevantResults($salmonResult['id'], $specialUses, $player['player_id']),
                        ],
                    );
                }

                return array_merge(
                    $salmonResult,
                    [
                        'player_results' => $playerResult,
                        'waves' => $waveResult,
                    ],
                );
            },
            $results,
        );

        return array_merge(
            $resultsWithPagination,
            ['results' => $results],
        );
    }

    private function filterRelevantResults(int $salmonId, array $array, ?string $playerId = null) {
        $hasPlayerId = $playerId !== null;

        return $this->valuesWithoutSalmonId(
            array_filter(
                $array,
                fn ($result) => $result['salmon_id'] === $salmonId &&
                    (!$hasPlayerId || $result['player_id'] === $playerId),
            ),
            $hasPlayerId,
        );
    }

    private function valuesWithoutSalmonId(array $array, bool $hasPlayerId) {
        return array_map(
            function ($value) use ($hasPlayerId) {
                unset($value['salmon_id']);
                if ($hasPlayerId) {
                    unset($value['player_id']);
                }

                return $value;
            },
            array_values($array),
        );
    }
}
