<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PrivacySettingsController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $user = $request->user();

        $newDisplayName = $request->display_name;

        if (preg_match('/[^\s　]/', $newDisplayName)) {
            $user->display_name = $newDisplayName;
        }
        else {
            $user->display_name = null;
        }

        if ($request->show_twitter_avatar) {
            $user->show_twitter_avatar = $request->show_twitter_avatar;
        }

        $user->save();

        return $user;
    }
}
