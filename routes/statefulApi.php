<?php

/*
|--------------------------------------------------------------------------
| Stateful API Routes
|--------------------------------------------------------------------------
|
| These endpoints are meant to be used internally.
|
*/

use Illuminate\Http\Request;

Route::get('/auth/twitter', 'Auth\TwitterAuthController@redirectToProvider');
Route::get('/auth/twitter/callback', 'Auth\TwitterAuthController@handleProviderCallback');
Route::get('/auth/twitter/logout', 'Auth\TwitterAuthController@logout');

Route::get('/metadata', function (Request $request) {
    $user = $request->user();

    $schedules = \App\SalmonSchedule::whereRaw('TIMESTAMPADD(WEEK, -1, CURRENT_TIMESTAMP) < schedule_id')
        ->whereRaw('schedule_id < TIMESTAMPADD(WEEK, 1, CURRENT_TIMESTAMP)')
        ->get();

    $response = [
        'user' => $user,
        'schedules' => $schedules,
    ];

    return $response;
});

Route::post('/settings/privacy', 'PrivacySettingsController');

Route::post('/upload-results', 'SalmonResultController@store');

Route::get('/api-token', function (Request $request) {
    $user = $request->user();

    if (empty($user)) {
        abort(401);
    }

    if ($request->query('regenerate') === 'true') {
        $apiToken = \App\Helpers\Helper::generateApiToken();
        $user->api_token = $apiToken;
        $user->save();
    }
    else {
        $apiToken = $user->api_token;
    }

    return ['api_token' => $apiToken];
});
