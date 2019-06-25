<?php

use Illuminate\Http\Request;
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

// Endpoints requires authentication
Route::group(['middleware' => ['auth:api']], function () {
    Route::post('/users/my', function (Request $request) {
        return $request->user();
    });
    Route::post('/results', function (Request $request) {
        $controller = new SalmonResultController;
        $controller->store($request, \Auth::user()->id);
    });
});
