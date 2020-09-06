<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateTwitterId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'salmon-stats:update-twitter-id {user_id} {new_twitter_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Twitter ID for user. This is used when someone lost access' .
        ' to their originally registered account.';

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
     * @return mixed
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        $twitterId = $this->argument('new_twitter_id');

        $user = \App\User::where('id', $userId)->firstOrFail();
        $name = $user->name;
        $confirmationText = "Are you sure to update `twitter_id` for user $userId ($name) to $twitterId?";

        if ($this->confirm($confirmationText)) {
            DB::transaction(function () use ($user, $twitterId) {
                $affected = $user->update([
                    'twitter_id' => $twitterId,
                ]);

                if ($affected === 0) {
                    throw new Exception('Failed to update `twitter_id`.');
                }
            });
        }
    }
}
