<?php

use Illuminate\Http\Request;
use App\SalmonResult;
use App\Http\Controllers\SalmonResultController;

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

// Public endpoints
Route::get('/results/{id}', 'SalmonResultController@show');

Route::get('/players/{player_id}', function (Request $request, $playerId) {
    // Player must have appeared in salmon_results at least once.
    if (!SalmonResult::whereJsonContains('members', $playerId)->exists()) {
        abort(404, "Player `$playerId` has no record.");
    }

    $user = \App\User::where('player_id', $playerId)->first();

    $resultController = new SalmonResultController();
    $results = $resultController->index($request, $playerId);
    $resultsWithoutPagination = $results->toArray()['data'];

    return [
        'user' => $user,
        'results' => $resultsWithoutPagination,
    ];
})->name('player.summary');
Route::get('/players/{player_id}/resuts', 'SalmonResultController@index')->name('player.results');

// Endpoints requires authentication
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
