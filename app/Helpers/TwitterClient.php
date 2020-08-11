<?php

namespace App\Helpers;

use Codebird\Codebird;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class TwitterClient {
    const BEARER_STORE_FILENAME = 'twitter-bearer';

    /**
     * @var Codebird
     */
    private $codebird;

    public function __construct()
    {
        $this->codebird = Codebird::getInstance();
    }

    public function initialize()
    {
        $consumerKey = Config::get('salmon-stats.TWITTER_CONSUMER_KEY');
        $consumerSecret = Config::get('salmon-stats.TWITTER_CONSUMER_SECRET');
        $storage = Storage::disk('local');

        if (empty($consumerKey) || empty($consumerSecret)) {
            throw new Exception('Environmental variables `TWITTER_CONSUMER_KEY` and `TWITTER_CONSUMER_SECRET` must be set.');
        }

        $bearer = '';
        if ($storage->exists(self::BEARER_STORE_FILENAME)) {
            $bearer = $storage->get(self::BEARER_STORE_FILENAME);
        }

        if (empty($bearer)) {
            $this->codebird->setConsumerKey($consumerKey, $consumerSecret);
            // Note: doc says oauth2_token() returns string; however, it returns stdClass
            $bearer = $this->codebird->oauth2_token()->access_token;

            $storage->put(self::BEARER_STORE_FILENAME, $bearer);
        }

        $this->codebird->setBearerToken($bearer);

        return $this;
    }

    /**
     * @return Codebird
     */
    public function getCodebird()
    {
        return $this->codebird;
    }
}
