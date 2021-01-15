<?php

namespace App\Http\Controllers\Auth;

use Laravel\Socialite\One\TwitterProvider;
use Laravel\Socialite\SocialiteManager;
use League\OAuth1\Client\Server\Twitter as TwitterServer;

class ConfigurableSocialiteManager extends SocialiteManager
{
    /**
     * Create an instance of the specified driver.
     *
     * @return \Laravel\Socialite\One\AbstractProvider
     */
    public function createTwitterDriverWithConfig(array $config)
    {
        return new TwitterProvider(
            $this->container->make('request'),
            new TwitterServer($this->formatConfig($config)),
        );
    }
}
