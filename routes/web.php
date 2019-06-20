<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/auth/twitter', 'Auth\TwitterAuthController@redirectToProvider');
Route::get('/auth/twitter/callback', 'Auth\TwitterAuthController@handleProviderCallback');
Route::get('/auth/twitter/logout', 'Auth\TwitterAuthController@logout');

Route::get('/login', function () {
    $signed_in_as = \Auth::user();

    if ($signed_in_as) {
        return redirect()->route("users", ['id' => $signed_in_as->id]);
    }
    else {
        return view('login');
    }
});
Route::get('/users/{id}', function ($userId) {
    $user = App\User::where('id', $userId)->first();

    if (!$user) {
        return abort(404);
    }

    return view('user', [
        'user' => $user,
    ]);
});
