<?php

namespace App\Helpers;

use App\SalmonPlayerResult;
use Illuminate\Database\Eloquent\Model;

class SalmonResultsFilterHelper {
    static private function buildWhere($column, $operator)
    {
        return fn ($results, $value) => $results->where($column, $operator, $value);
    }

    static private function buildMin($column)
    {
        return self::buildWhere($column, '>=');
    }

    static private function buildMax($column)
    {
        return self::buildWhere($column, '<=');
    }

    static function apply(Model $results, array $query, array $orderByArgs = [])
    {
        $model = $results->getModel();

        $filters = [
            'is_cleared' => function($results, $value) {
                $operator = $value === 'true' ? '=' : '<';
                return $results->where('clear_waves', $operator, 3);
            },
            'min_golden_egg' => self::buildMin('golden_egg_delivered'),
            'max_golden_egg' => self::buildMax('golden_egg_delivered'),
            'min_power_egg' => self::buildMin('power_egg_collected'),
            'max_power_egg' => self::buildMax('power_egg_collected'),
            'stages' => fn ($results, $value) => $results
                ->join('salmon_schedules', 'salmon_schedules.schedule_id', '=', 'salmon_results.schedule_id')
                ->whereIn('stage_id', explode(',', $value)),
            'sort_by' => function ($results, $value) use (&$orderByArgs, $query) {
                $sortableColumns = [
                    'golden_egg_delivered',
                    'player_golden_eggs',
                    'power_egg_collected',
                    'player_power_eggs',
                ];

                if (!in_array($value, $sortableColumns)) {
                    return;
                }

                $columnNameMap = [
                    'player_golden_eggs' => 'golden_eggs',
                    'player_power_eggs' => 'power_eggs',
                ];

                if (array_key_exists($value, $columnNameMap)) {
                    $value = $columnNameMap[$value];
                }

                $order = $query['sort_by_order'] ?? 'desc';
                $orderByArgs = [$value, $order];

                // Exclude "水没厳選" (intentionally giving up early)
                if ($order === 'asc') {
                    if (in_array($value, ['golden_egg_delivered', 'power_egg_collected'])) {
                        return $results->where('golden_egg_delivered', '>', 0);
                    }
                }
            },
        ];

        if ($model instanceof SalmonPlayerResult) {
            $filters += [
                'player_min_golden_egg' => self::buildMin('golden_eggs'),
                'player_max_golden_egg' => self::buildMax('golden_eggs'),
                'player_min_power_egg' => self::buildMin('power_eggs'),
                'player_max_power_egg' => self::buildMax('power_eggs'),
                'special' => self::buildWhere('special_id', '='),
                'weapons' => fn ($results, $value) => $results
                    ->join('salmon_player_weapons', function ($join) use ($value) {
                        $join
                            ->on('salmon_player_weapons.player_id', 'salmon_player_results.player_id')
                            ->on('salmon_player_weapons.salmon_id', 'salmon_results.id')
                            ->whereIn('weapon_id', explode(',', $value));
                    }),
            ];
        }

        foreach ($filters as $key => $filter) {
            if (isset($query[$key])) {
                $filterResult = $filter($results, $query[$key]);

                if (isset($filterResult)) {
                    $results = $filterResult;
                }
            }
        }

        if (!empty($orderByArgs)) {
            $results = $results->orderBy(...$orderByArgs);
        }

        return $results;
    }
}
