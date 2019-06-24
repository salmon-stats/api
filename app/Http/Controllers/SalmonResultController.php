<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Swaggest\JsonSchema\Schema;
use App\Exceptions\SalmonResultAlreadyExistsException;
use Illuminate\Support\Facades\Log;
use function GuzzleHttp\json_decode;

class SalmonResultController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
    public function store(Request $request, int $uploaderUserId)
    {
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

        $job = $request->input('splatnet_json');

        try {
            return DB::transaction(function () use ($job, $uploaderUserId) {
                $playerResults = array_merge([$job['my_result']], $job['other_results']);
                usort($playerResults, function ($a, $b) { return $a['pid'] < $b['pid'] ? 1 : -1; });

                $memberIds = array_map(function ($playerResult) {
                    return $playerResult['pid'];
                }, $playerResults);
                $failReason = $job['job_result']['failure_reason'];
                $clearWaves = $failReason ? $job['job_result']['failure_wave'] : 3; // TODO: Don't use magic number

                $existingSalmonResultId = DB::table('salmon_results')
                    ->select('id')
                    ->where('start_at', Carbon::parse($job['play_time']))
                    ->whereJsonContains('members', $memberIds)
                    ->first();

                if ($existingSalmonResultId) {
                    throw new SalmonResultAlreadyExistsException(
                        "Resource already exists. See /salmon-runs/{$existingSalmonResultId->id}"
                    );
                }

                $failReason = \App\SalmonFailReason::where(
                    'key', $job['job_result']['failure_reason']
                )->first();

                $salmonResult = [
                    'schedule_id' => Carbon::parse($job['start_time']),
                    'start_at' => Carbon::parse($job['play_time']),
                    'members' => json_encode($memberIds),
                    'uploader_user_id' => $uploaderUserId,
                    'clear_waves' => $clearWaves,
                    'fail_reason_id' => $failReason ? $failReason->id : null,
                    'danger_rate' => $job['danger_rate'],
                ];
                $createdSalmonResultId = DB::table('salmon_results')->insertGetId($salmonResult);

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

                    $salmonWave = [
                        'salmon_id' => $createdSalmonResultId,
                        'wave' => $waveIndex,
                        'event_id' => $event ? $event->id : null,
                        'water_id' => $waterLevel,
                        'golden_egg_quota' => $waveDetail['quota_num'],
                        'golden_egg_appearances' => $waveDetail['golden_ikura_pop_num'],
                        'golden_egg_delivered' => $waveDetail['golden_ikura_num'],
                        'power_egg_collected' => $waveDetail['ikura_num'],
                    ];
                    DB::table('salmon_waves')->insert($salmonWave);
                }

                foreach ($playerResults as $playerResult) {
                    $playerResultRow = [
                        'salmon_id' => $createdSalmonResultId,
                        'player_id' => $playerResult['pid'],
                        'golden_eggs' => $playerResult['golden_ikura_num'],
                        'power_eggs' => $playerResult['ikura_num'],
                        'rescue' => $playerResult['help_count'],
                        'death' => $playerResult['dead_count'],
                        'special_id' => (int) $playerResult['special']['id'],
                    ];
                    DB::table('salmon_player_results')->insert($playerResultRow);

                    $bossKillCounts = array_map(function ($boss) {
                        return $boss['count'];
                    }, $playerResult['boss_kill_counts']);

                    DB::table('salmon_player_boss_eliminations')->insert([
                        'salmon_id' => $createdSalmonResultId,
                        'player_id' => $playerResult['pid'],
                        'counts' => json_encode($bossKillCounts),
                    ]);

                    foreach ($waveIndexes as $waveIndex) {
                        DB::table('salmon_player_special_uses')->insert([
                            'salmon_id' => $createdSalmonResultId,
                            'player_id' => $playerResult['pid'],
                            'wave' => $waveIndex,
                            'count' => $playerResult['special_counts'][$waveIndex],
                        ]);

                        DB::table('salmon_player_weapons')->insert([
                            'salmon_id' => $createdSalmonResultId,
                            'player_id' => $playerResult['pid'],
                            'wave' => $waveIndex,
                            'weapon_id' => (int) $playerResult['weapon_list'][$waveIndex]['id'],
                        ]);
                    }
                }

                return response()->json(['salmon_result_id' => $createdSalmonResultId]);
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
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }
}
