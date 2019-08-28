<?php

namespace App\Helpers;

use Carbon\Carbon;
use GuzzleHttp\Client;
use function GuzzleHttp\json_decode;

class SalmonScheduleFetcher
{
    public function __construct()
    {
        $this->client = new Client();
    }

    public function fetchPastSchedules()
    {
        $schedules = $this->fetchFromYuu26();
        \App\SalmonSchedule::insert($this->filterExistingScheduls($schedules));
    }

    public function fetchFutureSchedules()
    {
        $schedules = $this->fetchFromSplatoon2Ink();
        \App\SalmonSchedule::insert($this->filterExistingScheduls($schedules));
    }

    private function filterExistingScheduls($schedules) {
        $latestScheduleId = \App\SalmonSchedule::max('schedule_id');
        return array_filter(
            $schedules,
            function ($schedule) use ($latestScheduleId) {
                return $schedule['schedule_id'] > $latestScheduleId;
            },
        );
    }

    private function fetchJson(String $url)
    {
        $response = $this->client->get($url);

        if ($response->getStatusCode() == 200) {
            return json_decode($response->getBody());
        }
        else {
            throw new RuntimeException("API: $url is currently unavailable.");
        }
    }

    private function fetchFromYuu26()
    {
        $schedules = $this->fetchJson('https://spla2.yuu26.com/coop')->result;

        return collect($schedules)
            ->filter(function ($schedule) { return !is_null($schedule->weapons); })
            ->map(function ($schedule) {
                $stageJapaneseNames = [
                    'シェケナダム' => 1,
                    '難破船ドン・ブラコ' => 2,
                    '海上集落シャケト場' => 3,
                    'トキシラズいぶし工房' => 4,
                    '朽ちた箱舟 ポラリス' => 5,
                ];
                $weapons = array_map(
                    function ($weapon) { return $weapon->id; },
                    $schedule->weapons,
                );
                $startTime = Carbon::parse($schedule->start_utc);
                $endTime = Carbon::parse($schedule->end_utc);

                return [
                    'schedule_id' => $startTime,
                    'end_at' => $endTime,
                    'weapons' => json_encode($weapons),
                    'stage_id' => $stageJapaneseNames[$schedule->stage->name],
                ];
            })
            ->toArray();
    }

    private function fetchFromSplatoon2Ink()
    {
        $schedules = $this->fetchJson('https://splatoon2.ink/data/coop-schedules.json')->details;

        return collect($schedules)
            ->map(function ($schedule) {
                $stageEnglishNames = [
                    'Spawning Grounds' => 1,
                    'Marooner\'s Bay' => 2,
                    'Lost Outpost' => 3,
                    'Salmonid Smokeyard' => 4,
                    'Ruins of Ark Polaris' => 5,
                ];
                $weapons = array_map(
                    function ($weapon) { return $weapon->id; },
                    $schedule->weapons,
                );
                $startTime = Carbon::createFromTimestamp($schedule->start_time);
                $endTime = Carbon::createFromTimestamp($schedule->end_time);

                return [
                    'schedule_id' => $startTime,
                    'end_at' => $endTime,
                    'weapons' => json_encode($weapons),
                    'stage_id' => $stageEnglishNames[$schedule->stage->name],
                ];
            })
            ->toArray();
    }
}
