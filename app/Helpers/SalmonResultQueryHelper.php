<?php

namespace App\Helpers;

use App\SalmonPlayerBossElimination;
use App\SalmonPlayerResult;
use App\SalmonPlayerSpecialUse;
use App\SalmonPlayerWeapon;
use App\SalmonWave;

class SalmonResultQueryHelper
{
    /**
     * @param array|object $results
     * @return array $results
     */
    static public function queryFullResults($results)
    {
        $results = isset($results['id']) ? func_get_args() : $results; // Assumes non-array if `id` key exists

        $ids = array_map(
            fn ($result) => $result['id'],
            $results,
        );

        $playerBossEliminations = SalmonPlayerBossElimination::whereIn('salmon_id', $ids)->get()->makeVisible(['salmon_id', 'player_id'])->toArray();
        $playerResults = SalmonPlayerResult::whereIn('salmon_id', $ids)->get()->makeVisible('salmon_id')->toArray();
        $playerWeapons = SalmonPlayerWeapon::whereIn('salmon_id', $ids)->get()->makeVisible(['salmon_id', 'player_id'])->toArray();
        $waveResults = SalmonWave::whereIn('salmon_id', $ids)->get()->makeVisible('salmon_id')->toArray();
        $specialUses = SalmonPlayerSpecialUse::whereIn('salmon_id', $ids)->get()->makeVisible(['salmon_id', 'player_id'])->toArray();

        return array_map(
            function ($salmonResult) use ($playerBossEliminations, $playerResults, $playerWeapons, $waveResults, $specialUses) {
                $playerResult = self::filterRelevantResults($salmonResult['id'], $playerResults);
                $waveResult = self::filterRelevantResults($salmonResult['id'], $waveResults);

                foreach ($playerResult as &$player) {
                    $player = array_merge(
                        $player,
                        [
                            'boss_eliminations' => self::filterRelevantResults($salmonResult['id'], $playerBossEliminations, $player['player_id'])[0],
                            'special_uses' => self::filterRelevantResults($salmonResult['id'], $specialUses, $player['player_id']),
                            'weapons' => self::filterRelevantResults($salmonResult['id'], $playerWeapons, $player['player_id']),
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
    }

    static private function filterRelevantResults(int $salmonId, array $array, ?string $playerId = null) {
        $hasPlayerId = $playerId !== null;

        return self::valuesWithoutSalmonId(
            array_filter(
                $array,
                fn ($result) => $result['salmon_id'] === $salmonId &&
                    (!$hasPlayerId || $result['player_id'] === $playerId),
            ),
            $hasPlayerId,
        );
    }

    static private function valuesWithoutSalmonId(array $array, bool $hasPlayerId) {
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
