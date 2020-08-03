<?php

namespace App\Providers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\UrlGenerator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(UrlGenerator $url)
    {
        if (\App::environment() === 'production') {
          $url->forceScheme('https');
        }

        $this->app->resolving(LengthAwarePaginator::class, static function (LengthAwarePaginator $paginator) {
            return $paginator->appends(request()->query());
        });
        $this->app->resolving(Paginator::class, static function (Paginator $paginator) {
            return $paginator->appends(request()->query());
        });
    }
}
