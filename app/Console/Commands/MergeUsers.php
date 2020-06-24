<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MergeUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'salmon-stats:merge-users {secondary} {primary}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Merge users using multiple Twitter accounts into single user.\n" .
        'This command is intended to be used for pre-multiple-account-support users.';

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
        $secondary = $this->argument('secondary');
        $primary = $this->argument('primary');
        $confirmationText = "Are you sure to DELETE `$secondary` and merge its data into `$primary`?\n" .
            'This action cannot be undone.';

        if ($this->confirm($confirmationText)) {
            DB::transaction(function () use ($primary, $secondary) {
                $primaryUser = \App\User::where('name', $primary)->firstOrFail();
                $secondaryUser = \App\User::where('name', $secondary)->firstOrFail();
                $secondaryAccount = \App\UserAccount::where('user_id', $secondaryUser->id);
                $affected = $secondaryAccount->update([
                    'user_id' => $primaryUser->id,
                    'is_primary' => false,
                ]);

                if ($affected === 0) {
                    throw new Exception('Failed to update UserAccount.');
                }

                $affected = \App\SalmonResult::where('uploader_user_id', $secondaryUser->id)
                    ->update([
                        'uploader_user_id' => $primaryUser->id,
                    ]);

                $this->info("Updated `salmon_results.uploader_user_id` ($affected rows).");

                $secondaryUser->delete();
            });
        }
    }
}
