<?php

use Illuminate\Http\Request;

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

Route::get('/players/{player_id}', 'SalmonResultController@index')->name('player/summary');

// Endpoints requires authentication
Route::group(['middleware' => ['auth:api']], function () {
    Route::post('/results', 'SalmonResultController@store');

    Route::get('/users/my', function (Request $request) {
        $user = $request->user();

        if (!$user->player_id) {
            abort(404, 'You don\'t have uploaded results yet.');
        }

        return redirect()->route('player/summary', [$user->player_id]);
    });
});
