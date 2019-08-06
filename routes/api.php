<?php

use Illuminate\Http\Request;
use App\SalmonResult;
use App\Http\Controllers\SalmonResultController;
use App\User;
use App\Helpers\Helper;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*
 * Public endpoints
 */
Route::get('/results', 'SalmonResultController@index', 'results.index');

Route::get('/results/{id}', 'SalmonResultController@show', 'results.show');

Route::get('/id-key-map', function () {
    $bosses = Helper::makeIdTokeyMap(
        \App\SalmonBoss::get()
    );
    $events = Helper::makeIdTokeyMap(
        \App\SalmonEvent::get()
    );
    $failReasons = Helper::makeIdTokeyMap(
        \App\SalmonFailReason::get()
    );
    $waterLevels = Helper::makeIdTokeyMap(
        \App\SalmonWaterLevel::get()
    );
    $specials = Helper::makeIdTokeyMap(
        \App\SalmonSpecial::get()
    );
    $stages = Helper::makeIdTokeyMap(
        \App\SalmonStage::get()
    );
    $weapons = Helper::makeIdTokeyMap(
        \App\SalmonWeapon::get()
    );
    return [
        'boss' => $bosses,
        'event' => $events,
        'fail_reason' => $failReasons,
        'special' => $specials,
        'stage' => $stages,
        'water_level' => $waterLevels,
        'weapon' => $weapons,
    ];
});

// player routes
Route::get('/players/{player_id}', function (Request $request, $playerId) {
    // Player must have appeared in salmon_results at least once.
    if (!SalmonResult::whereJsonContains('members', $playerId)->exists()) {
        abort(404, "Player `$playerId` has no record.");
    }

    $user = User::where('player_id', $playerId)->first();

    $resultController = new SalmonResultController();
    $resultController->setRowsPerPage(10);
    $results = $resultController->index($request, $playerId);
    $resultsWithoutPagination = $results->toArray()['data'];

    return [
        'user' => $user,
        'results' => $resultsWithoutPagination,
    ];
})->name('player.summary');

Route::get('/players/{player_id}/results', 'SalmonResultController@index')->name('player.results');

Route::get('/players/@/{screen_name}', function (Request $request, string $screenName) {
    try {
        $user = User::where('name', $screenName)->firstOrFail();
    } catch (Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        abort(404);
    }

    if (!$user->player_id) {
        abort(404);
    }

    return redirect()->route('player.summary', [$user->player_id]);
});

Route::get('/schedules/{schedule_id}/results', 'SalmonResultController@index', 'schedules.results');

Route::get('/schedules/{schedule_id}/records', function (Request $request, string $scheduleId) {
    if (preg_match('/^\\d{4}-\\d{2}-\\d{2} \\d{2} \\d{2}$/', $scheduleId)) {
        abort(422, 'Invalid schedule id');
    }

    $queries = [
        ['golden_egg_delivered', 'golden_eggs'],
        ['power_egg_collected', 'power_eggs'],
    ];

    function buildTotalEggQuery($query) {
        $totalEggsQuery = <<<QUERY
WITH salmon_ids AS (
    SELECT id FROM salmon_results
    WHERE schedule_id = ?
),
total_golden_eggs AS (
    SELECT salmon_id, CAST(SUM($query[0]) AS SIGNED) AS $query[1]
    FROM salmon_ids
    LEFT OUTER JOIN salmon_waves ON salmon_waves.salmon_id = salmon_ids.id
    GROUP BY salmon_id
)
SELECT salmon_id AS id, $query[1]
    FROM total_golden_eggs
    ORDER BY $query[1] DESC
    LIMIT 1
QUERY;
        return $totalEggsQuery;
    }

    function buildTideXEventRecordsQuery($query) {
        $queryBuilt = <<<QUERY
WITH salmon_ids AS (
    SELECT id FROM salmon_results
    WHERE schedule_id = ?
),
events_with_null AS (
    SELECT id FROM salmon_water_levels UNION SELECT NULL
),
wave_results AS (
    SELECT * FROM salmon_ids
        INNER JOIN salmon_waves ON salmon_ids.id = salmon_waves.salmon_id
),
water_x_event AS (
    SELECT water_level_ids.id AS water_id, salmon_events.id AS event_id
        FROM salmon_events
    CROSS JOIN
    (SELECT salmon_water_levels.id FROM salmon_water_levels) AS water_level_ids
),
records AS (
    SELECT
            id,
            wave_results.water_id,
            CASE WHEN wave_results.event_id IS NULL THEN 0 ELSE wave_results.event_id END AS event_id,
            $query[0] AS $query[1],
            ROW_NUMBER() OVER (PARTITION BY wave_results.water_id, wave_results.event_id ORDER BY $query[0] DESC) AS row_num
        FROM water_x_event
        INNER JOIN wave_results ON water_x_event.water_id = wave_results.water_id
            AND (water_x_event.event_id = wave_results.event_id
                OR (water_x_event.event_id IS NULL AND wave_results.event_id IS NULL))
)
SELECT id, water_id, event_id, $query[1] FROM records WHERE row_num = 1
QUERY;
        return $queryBuilt;
    }

    $response = [];

    try {
        foreach ($queries as $query) {
            $response['totals'][$query[1]] = DB::select(buildTotalEggQuery($query), [$scheduleId])[0];
            $response['tides'][$query[1]] = DB::select(buildTideXEventRecordsQuery($query), [$scheduleId]);
        }
    }
    catch (Illuminate\Database\QueryException $e) {
        abort(422, 'Invalid schedule id');
    }
    catch (\Exception $e) {
        abort(500, "Unhandled Exception: {$e->getMessage()}");
    }

    return $response;
}, 'schedules.records');

/*
 * Endpoints requires authentication
 */
Route::group(['middleware' => ['auth:api']], function () {
    Route::post('/results', 'SalmonResultController@store');

    Route::get('/users/my', function (Request $request) {
        $user = $request->user();

        if (!$user->player_id) {
            abort(404, 'You don\'t have uploaded results yet.');
        }

        return redirect()->route('player.summary', [$user->player_id]);
    });
});
