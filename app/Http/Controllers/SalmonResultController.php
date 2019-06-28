<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Swaggest\JsonSchema\Schema;
use App\Exceptions\SalmonResultAlreadyExistsException;
use Illuminate\Support\Facades\Log;
use function GuzzleHttp\json_decode;
use App\SalmonResult;

class SalmonResultController extends Controller
{
    protected $rowsPerPage = 20;

    public function setRowsPerPage(int $rowsPerPage)
    {
        $this->rowsPerPage = $rowsPerPage;
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string $playerId
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, string $playerId)
    {
        return SalmonResult::whereJsonContains('members', $playerId)
            ->orderBy('start_at', 'desc')
            ->paginate($this->rowsPerPage);
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

        $job = $request->input('results')[0];

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
            return DB::transaction(function () use ($job, $user, $jobPlayerId) {
                $playerResults = array_merge([$job['my_result']], $job['other_results']);
                usort($playerResults, function ($a, $b) { return $a['pid'] > $b['pid'] ? 1 : -1; });

                $memberIds = array_map(function ($playerResult) {
                    return $playerResult['pid'];
                }, $playerResults);
                $failReason = $job['job_result']['failure_reason'];
                $clearWaves = $failReason ? $job['job_result']['failure_wave'] : 3; // TODO: Don't use magic number

                $existingSalmonResult =
                    SalmonResult::where('start_at', Carbon::parse($job['play_time']))
                        // Note: [1] can match with [1,2] but start_at makes it identical
                        ->whereJsonContains('members', $memberIds)
                        ->first();

                if ($existingSalmonResult) {
                    throw new SalmonResultAlreadyExistsException(
                        "Resource already exists. See /results/{$existingSalmonResult->id}"
                    );
                }

                $failReason = \App\SalmonFailReason::where(
                    'key', $job['job_result']['failure_reason']
                )->first();

                $bossAppearances = array_map(function ($boss) {
                    return $boss['count'];
                }, $job['boss_counts']);

                $salmonResult = new SalmonResult();
                $salmonResult
                    ->fill([
                        'schedule_id' => Carbon::parse($job['start_time']),
                        'start_at' => Carbon::parse($job['play_time']),
                        'members' => $memberIds,
                        'boss_appearances' => $bossAppearances,
                        'uploader_user_id' => $user->id,
                        'clear_waves' => $clearWaves,
                        'fail_reason_id' => $failReason ? $failReason->id : null,
                        'danger_rate' => $job['danger_rate'],
                    ])
                    ->save();

                $waveIndexes = range(0, $clearWaves === 0 ? 0 : $clearWaves - 1);
                $waveDetails = $job['wave_details'];
                foreach ($waveIndexes as $waveIndex) {
                    $waveDetail = $waveDetails[$waveIndex];
                    // You don't have to validate event_type and water_level
                    // because it's already done by json schema.

                    // $event = null if key is 'water-levels'
                    $event = \App\SalmonEvent::where(
                        'splatnet', $waveDetail['event_type']['key']
                    )->first();
                    $waterLevel = \App\SalmonWaterLevel::where(
                        'splatnet', $waveDetail['water_level']['key'],
                    )->first()->id;

                    \App\SalmonWave::create([
                        'salmon_id' => $salmonResult->id,
                        'wave' => $waveIndex + 1,
                        'event_id' => $event ? $event->id : null,
                        'water_id' => $waterLevel,
                        'golden_egg_quota' => $waveDetail['quota_num'],
                        'golden_egg_appearances' => $waveDetail['golden_ikura_pop_num'],
                        'golden_egg_delivered' => $waveDetail['golden_ikura_num'],
                        'power_egg_collected' => $waveDetail['ikura_num'],
                    ]);
                }

                foreach ($playerResults as $playerResult) {
                    \App\SalmonPlayerResult::create([
                        'salmon_id' => $salmonResult->id,
                        'player_id' => $playerResult['pid'],
                        'golden_eggs' => $playerResult['golden_ikura_num'],
                        'power_eggs' => $playerResult['ikura_num'],
                        'rescue' => $playerResult['help_count'],
                        'death' => $playerResult['dead_count'],
                        'special_id' => (int) $playerResult['special']['id'],
                    ]);

                    $bossKillCounts = array_map(function ($boss) {
                        return $boss['count'];
                    }, $playerResult['boss_kill_counts']);

                    \App\SalmonPlayerBossElimination::create([
                        'salmon_id' => $salmonResult->id,
                        'player_id' => $playerResult['pid'],
                        'counts' => $bossKillCounts,
                    ]);

                    foreach ($waveIndexes as $waveIndex) {
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

                return response()->json(['salmon_result_id' => $salmonResult->id]);
            });
        }
        catch (SalmonResultAlreadyExistsException $e) {
            abort(409, $e->getMessage());
        }
        catch (\Exception $e) {
            Log::error($e);
            abort(500, "Unhandled Exception: {$e->getMessage()}");
        }
    }

    /**
     * Display the specified resource.
     * @param  int  $salmonId
     * @return \Illuminate\Http\Response
     */
    public function show($salmonId)
    {
        $salmonResult = SalmonResult::where('id', $salmonId)
            ->with(['playerResults', 'schedule', 'waves'])
            ->firstOrFail();

        return $salmonResult;
    }
}
