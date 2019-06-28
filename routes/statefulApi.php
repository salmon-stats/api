<?php

/*
|--------------------------------------------------------------------------
| Stateful API Routes
|--------------------------------------------------------------------------
|
| These endpoints are meant to be used internally.
|
*/

use Illuminate\Support\Facades\Request;

Route::get('/auth/twitter', 'Auth\TwitterAuthController@redirectToProvider');
Route::get('/auth/twitter/callback', 'Auth\TwitterAuthController@handleProviderCallback');
Route::get('/auth/twitter/logout', 'Auth\TwitterAuthController@logout');

Route::get('/metadata', function (Request $request) {
    $user = $request::user();

    if ($user) {
        $user->addHidden(['created_at', 'updated_at']);
    }

    $response = [
        'user' => $user,
    ];

    return $response;
});

Route::post('/upload-results', function (Request $request) {
    return [1];
});

Route::get('/api-token', function (Request $request) {
    $user = $request::user();

    if (empty($user)) {
        abort(401);
    }

    $newApiToken = \App\Helpers\Helper::generateApiToken();
    $user->api_token = $newApiToken;
    $user->save();

    return ['api_token' => $newApiToken];
});
