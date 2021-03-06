<?php

/*
|--------------------------------------------------------------------------
| Stateful API Routes
|--------------------------------------------------------------------------
|
| These endpoints are meant to be used internally.
|
*/

use App\Http\Controllers\Auth\TwitterAuthController;
use Illuminate\Http\Request;

Route::get('/auth/twitter', function (Request $request) {
    $user = $request->user();
    if ($user !== null) {
        return redirect(TwitterAuthController::getDestination($request, $user))->withCookie(TwitterAuthController::clearAppTokenCookie());
    }

    return app()->call('App\Http\Controllers\Auth\TwitterAuthController@redirectToProvider');
})->name('auth.twitter');
Route::get('/auth/twitter/callback', 'Auth\TwitterAuthController@handleProviderCallback');
Route::get('/auth/twitter/logout', 'Auth\TwitterAuthController@logout');

Route::get('/app-request-api-token', function (Request $request) {
    return redirect()->route('auth.twitter')->withCookie('app-request-api-token', 'true');
});

Route::get('/metadata', function (Request $request) {
    $user = $request->user();

    if (isset($user)) {
        $user->load('accounts', 'accounts.name');
    }

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
