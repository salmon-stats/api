<?php

namespace App\Http\Controllers\Auth;
use App\User;
use Auth;
use Socialite;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

class TwitterAuthController extends Controller
{
    use AuthenticatesUsers;

    static public function getDestination(Request $request = null, $user = null): String
    {
        $frontend = env('APP_FRONTEND_ORIGIN');

        if (($request ?? request())->cookie('app-request-api-token') !== null) {
            return $frontend . "/settings#app-request-api-token";
        } else if ($user !== null) {
            return $user->getPlayerPage() ?? $frontend;
        }

        return $frontend;
    }

    static public function clearAppTokenCookie(): Cookie
    {
        return \Cookie()->forget('app-request-api-token');
    }

    public function redirectToProvider()
    {
        return Socialite::driver('twitter')->redirect();
    }

    public function handleProviderCallback()
    {
        try {
            $user = Socialite::driver('twitter')->user();
        } catch (Exception $e) {
            \Log::error($e);
            return redirect('/');
        }

        $authUser = $this->findOrCreateUser($user);

        Auth::login($authUser, true);

        return redirect($this->getDestination(null, $authUser))->withCookie($this::clearAppTokenCookie());
    }

    private function replaceHttpWithHttps($url)
    {
        return preg_replace('/^http:/i', 'https:', $url);
    }

    private function findOrCreateUser($twitterUser)
    {
        $authUser = User::where('twitter_id', $twitterUser->id)->first();

        if ($authUser) {
            $authUser->twitter_avatar = $this->replaceHttpWithHttps($twitterUser->avatar_original);
            $authUser->save();
            return $authUser;
        }

        return User::create([
            // Use twitter screen name (@example without `@`) as name
            'name' => strtolower($twitterUser->nickname),
            'twitter_id' => $twitterUser->id,
            // api_token must be unique; 256-bit hash won't practically collide.
            'api_token' => \App\Helpers\Helper::generateApiToken(),
            'twitter_avatar' => $this->replaceHttpWithHttps($twitterUser->avatar_original),
        ]);
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('index');
    }

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
}
