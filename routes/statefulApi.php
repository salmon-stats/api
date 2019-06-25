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

Route::get('/metadata', function (Request $request) {
    $user = $request::user();

    if ($user) {
        $user->makeVisible('api_token');
    }

    $response = [
        'user' => $user,
    ];

    return $response;
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
