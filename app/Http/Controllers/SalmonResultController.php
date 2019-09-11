<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Swaggest\JsonSchema\Schema;
use Illuminate\Support\Facades\Log;
use function GuzzleHttp\json_decode;
use App\SalmonResult;
use App\UseCases\IndexResultUsecase;

class SalmonResultController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string $playerId
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, IndexResultUsecase $usecase)
    {
        $scheduleTimestamp = \App\Helpers\Helper::scheduleIdToTimestamp($request->schedule_id);
        return $usecase($request->player_id, $scheduleTimestamp, $request->route()->getName());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    /*
    public function create()
    {
        //
    }
    */

    protected function createRecords($job, $user, $jobPlayerId)
    {
        return function () use ($job, $user, $jobPlayerId) {
            $playerJobId = $job['job_id'] ?: null;
            $playerResults = array_merge([$job['my_result']], $job['other_results']);
            usort($playerResults, function ($a, $b) { return $a['pid'] > $b['pid'] ? 1 : -1; });

            $memberIds = array_map(function ($playerResult) {
                return $playerResult['pid'];
            }, $playerResults);
            $failReason = $job['job_result']['failure_reason'];
            $wavesCleared = $failReason ? $job['job_result']['failure_wave'] - 1 : 3; // TODO: Don't use magic number

            $existingSalmonResult =
                SalmonResult::where('start_at', '>', Carbon::parse($job['play_time'] - 60))
                    ->where('start_at', '<', Carbon::parse($job['play_time'] + 60))
                    // Note: [1] can match with [1,2] but start_at makes it identical
                    ->whereJsonContains('members', $memberIds)
                    ->first();

            if ($existingSalmonResult) {
                return [
                    'created' => false,
                    'job_id' => $playerJobId,
                    'salmon_id' => $existingSalmonResult->id,
                ];
            }

            $failReason = \App\SalmonFailReason::where(
                'key', $job['job_result']['failure_reason']
            )->first();

            $bossAppearances = \App\Helpers\Helper::mapCount($job['boss_counts']);
            $bossEliminationCount = array_sum(
                array_map(function ($playerResult) {
                    return array_sum(\App\Helpers\Helper::mapCount($playerResult['boss_kill_counts']));
                }, $playerResults),
            );

            $salmonResult = new SalmonResult();
            $salmonResult
                ->fill([
                    'schedule_id' => Carbon::parse($job['start_time']),
                    'start_at' => Carbon::parse($job['play_time']),
                    'members' => $memberIds,
                    'boss_appearances' => $bossAppearances,
                    'uploader_user_id' => $user->id,
                    'clear_waves' => $wavesCleared,
                    'fail_reason_id' => $failReason ? $failReason->id : null,
                    'danger_rate' => $job['danger_rate'],
                    'golden_egg_delivered' => array_reduce(
                        $job['wave_details'],
                        function ($sum, $wave) { return $sum + $wave['golden_ikura_num']; },
                        0,
                    ),
                    'power_egg_collected' => array_reduce(
                        $job['wave_details'],
                        function ($sum, $wave) { return $sum + $wave['ikura_num']; },
                        0,
                    ),
                    'boss_appearance_count' => array_sum($bossAppearances),
                    'boss_elimination_count' => $bossEliminationCount,
                ])
                ->save();

            $waveDetails = $job['wave_details'];
            $wavesPlayed = sizeof($waveDetails);
            $waveIndices = range(0, $wavesCleared === 0 ? 0 : $wavesPlayed - 1);
            foreach ($waveIndices as $waveIndex) {
                $waveDetail = $waveDetails[$waveIndex];
                // You don't have to validate event_type and water_level
                // because it's already done by json schema.

                $eventId = \App\SalmonEvent::where(
                    'splatnet', $waveDetail['event_type']['key']
                )->first()->id;
                $waterLevelId = \App\SalmonWaterLevel::where(
                    'splatnet', $waveDetail['water_level']['key'],
                )->first()->id;

                \App\SalmonWave::create([
                    'salmon_id' => $salmonResult->id,
                    'wave' => $waveIndex + 1,
                    'event_id' => $eventId,
                    'water_id' => $waterLevelId,
                    'golden_egg_quota' => $waveDetail['quota_num'],
                    'golden_egg_appearances' => $waveDetail['golden_ikura_pop_num'],
                    'golden_egg_delivered' => $waveDetail['golden_ikura_num'],
                    'power_egg_collected' => $waveDetail['ikura_num'],
                ]);
            }

            foreach ($playerResults as $playerResult) {
                $bossKillCounts = \App\Helpers\Helper::mapCount($playerResult['boss_kill_counts']);

                \App\SalmonPlayerResult::create([
                    'salmon_id' => $salmonResult->id,
                    'player_id' => $playerResult['pid'],
                    'golden_eggs' => $playerResult['golden_ikura_num'],
                    'power_eggs' => $playerResult['ikura_num'],
                    'rescue' => $playerResult['help_count'],
                    'death' => $playerResult['dead_count'],
                    'special_id' => (int) $playerResult['special']['id'],
                    'boss_elimination_count' => array_sum($bossKillCounts),
                ]);

                // updateOrCreate can't be used as upsert here.
                $updateplayerNamesQuery = <<<QUERY
INSERT into salmon_player_names (player_id, name, created_at, updated_at)
    VALUES (?, ?, CURRENT_TIMESTAMP, ?)
    ON DUPLICATE KEY UPDATE
        name = IF(updated_at < ?, ?, name),
        updated_at = IF(updated_at < ?, ?, updated_at)
QUERY;
                DB::insert(
                    $updateplayerNamesQuery,
                    [
                        $playerResult['pid'],
                        $playerResult['name'],
                        Carbon::parse($job['play_time']),
                        Carbon::parse($job['play_time']),
                        $playerResult['name'],
                        Carbon::parse($job['play_time']),
                        Carbon::parse($job['play_time']),
                    ],
                );

                \App\SalmonPlayerBossElimination::create([
                    'salmon_id' => $salmonResult->id,
                    'player_id' => $playerResult['pid'],
                    'counts' => $bossKillCounts,
                ]);

                foreach ($waveIndices as $waveIndex) {
                    try {
                        \App\SalmonPlayerSpecialUse::create([
                            'salmon_id' => $salmonResult->id,
                            'player_id' => $playerResult['pid'],
                            'wave' => $waveIndex,
                            'count' => $playerResult['special_counts'][$waveIndex],
                        ]);

                        \App\SalmonPlayerWeapon::create([
                            'salmon_id' => $salmonResult->id,
                            'player_id' => $playerResult['pid'],
                            'wave' => $waveIndex,
                            'weapon_id' => (int) $playerResult['weapon_list'][$waveIndex]['id'],
                        ]);
                    } catch (\ErrorException $e) {
                        // ...[$waveIndex] doesn't exist because player disconnected
                    }
                }
            }

            $user->player_id = $jobPlayerId;
            $user->save();

            return [
                'created' => true,
                'job_id' => $playerJobId,
                'salmon_id' => $salmonResult->id,
            ];
        };
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $schema = Schema::import(json_decode(
            file_get_contents(base_path() . '/schemas/upload-salmon-result.json')
        ));

        try {
            $schema->in(json_decode($request->getContent()));
        }
        catch (\InvalidArgumentException $e) {
            abort(400, 'Invalid JSON');
        }
        catch (\Swaggest\JsonSchema\Exception $e) {
            abort(400, $e->getMessage());
        }

        // TODO:
        foreach ($request->input('results') as $job) {
            $jobPlayerId = $job['my_result']['pid'];
            $associatedUser = \App\User::where('player_id', $jobPlayerId)
                ->first();

            if ($associatedUser && $user->id !== $associatedUser->id) {
                abort(403, "Player `{$associatedUser->id}` is associated with different user.");
            }
            elseif ($user->player_id && $user->player_id !== $jobPlayerId) {
                abort(403, 'You cannot upload different player\'s result.');
            }

            try {
                $results[] = DB::transaction($this->createRecords($job, $user, $jobPlayerId));
            }
            catch (\Exception $e) {
                Log::error($e);
                abort(500, "Unhandled Exception: {$e->getMessage()}");
            }
        }

        return response()->json($results);
    }

    /**
     * Display the specified resource.
     * @param  int  $salmonId
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        if (isset($request->player_id))  {
            $query = SalmonResult::whereJsonContains('members', $request->player_id)
                ->orderBy('id', 'desc');
        }
        elseif (isset($request->salmon_id)) {
            $query = SalmonResult::where('id', $request->salmon_id);
        }

        return $query
            ->with(['playerResults', 'schedule', 'waves'])
            ->firstOrFail()
            ->append('member_accounts');
    }
}
