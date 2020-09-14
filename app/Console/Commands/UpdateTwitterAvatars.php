<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\TwitterClient;
use App\User;
use Illuminate\Support\Facades\DB;

class UpdateTwitterAvatars extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'salmon-stats:update-twitter-avatars {--all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Updates users' `twitter_avatar` from Twitter API. If no --all option is set, updates 100 random users.";


    /**
     * @var Codebird
     */
    private $codebird;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int|null
     */
    public function handle()
    {
        $this->codebird = (new TwitterClient())->initialize()->getCodebird();

        if ($this->option('all')) {
            // Note: This code supports up to 30,000 usesr (300 requests/15-min window * 100 users per request)
            // https://developer.twitter.com/en/docs/accounts-and-users/follow-search-get-users/api-reference/get-users-lookup
            $transactionCallback = function () {
                User::query()->chunk(100, fn ($users) => $this->updateAvatarUrls($users));
            };
        } else {
            $transactionCallback = function () {
                $queryResult = DB::select(<<<QUERY
                SELECT users.id, users_self.twitter_id
                    FROM users
                    INNER JOIN users AS users_self ON users.id = users_self.id
                    ORDER BY RAND()
                    LIMIT 100
                QUERY);

                $this->updateAvatarUrls(User::hydrate($queryResult));
            };
        }

        DB::transaction($transactionCallback);
    }

    private function updateAvatarUrls($users)
    {
        $userIds = collect($users)
            ->map(fn ($user) => $user->twitter_id)
            ->join(',');

        $this->info(__CLASS__ . ": Calling Twitter API: /users/lookup?user_id=$userIds");

        $twitterUsers = (array) $this->codebird->users_lookup("include_entities=false&user_id=$userIds", true);

        foreach ($twitterUsers as $key => $twitterUser) {
            if (is_string($key)) {
                continue;
            }

            $userToUpdate = collect($users)->first(fn ($user) => $user->twitter_id === $twitterUser->id);
            if (!empty($userToUpdate)) {
                $originalProfileImage = str_replace("_normal.", ".", $twitterUser->profile_image_url_https);
                $userToUpdate->name = $twitterUser->screen_name;
                $userToUpdate->twitter_avatar = $originalProfileImage;
                $userToUpdate->save();
            }
        }
    }
}
