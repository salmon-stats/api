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

                $salmonResult = [
                    'schedule_id' => Carbon::parse($job['start_time']),
                    'start_at' => Carbon::parse($job['play_time']),
                    'members' => json_encode($memberIds),
                    'uploader_user_id' => $uploaderUserId,
                    'clear_waves' => $clearWaves,
                    'fail_reason_id' => null, // TODO: $failReason
                    'danger_rate' => $job['danger_rate'],
                ];
                $createdSalmonResultId = DB::table('salmon_results')->insertGetId($salmonResult);

                return response()->json(['salmon_result_id' => $createdSalmonResultId]);
            });
        }
        catch (SalmonResultAlreadyExistsException $e) {
            abort(409, $e->getMessage());
        }
        catch (\Exception $e) {
            Log::error($e);
            abort(500, "Unhandled Exception: {$e->getCode()}");
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
