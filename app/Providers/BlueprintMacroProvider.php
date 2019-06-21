<?php

namespace App\Providers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\ServiceProvider;

class BlueprintMacroProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        Blueprint::macro('statInkKey', function(string $key, int $length = 16) {
            $this->string($key, $length);
        });
    }
}
