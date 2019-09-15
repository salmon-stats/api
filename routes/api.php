<?php

use Illuminate\Http\Request;
use App\SalmonResult;
use App\Http\Controllers\SalmonResultController;
use App\Http\Controllers\ScheduleRecordController;
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
})->name('id-key-map');

// Results routes
Route::get('/results', 'SalmonResultController@index')->name('results');
Route::get('/results/{salmon_id}', 'SalmonResultController@show')->name('results.show');

// Players routes
Route::get('/players/{screen_name}', function (Request $request, string $screenNameWithAt) {
    $screenName = strtolower(str_replace('@', '', $screenNameWithAt));

    try {
        $user = User::where('name', $screenName)->firstOrFail();
    } catch (Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        abort(404);
    }

    if (!$user->player_id) {
        abort(404);
    }

    $request->merge(['player_id' => $user->player_id]);

    return app()->call('App\Http\Controllers\SalmonPlayerController@index');
})->where('screen_name', '^@\w{1,15}');
Route::get('/players/metadata', 'SalmonPlayerMetadata')->name('players.metadata');
Route::get('/players/{player_id}', 'SalmonPlayerController@index')->name('players.summary');
Route::get('/players/{player_id}/results', 'SalmonResultController@index')->name('players.results');
Route::get('/players/{player_id}/results/latest', 'SalmonResultController@show')->name('players.results.latest');

// Schedules routes
Route::get('/schedules','SalmonScheduleController@index')->name('schedules');
Route::get('/schedules/{schedule_id}','SalmonScheduleController@show')->name('schedules.summary');
Route::get('/schedules/{schedule_id}/metadata','SalmonScheduleMetadata')->name('schedules.metadata');
Route::get('/schedules/{schedule_id}/results','SalmonScheduleController@show')->name('schedules.results');

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

        return redirect()->route('players.summary', [$user->player_id]);
    });
});
