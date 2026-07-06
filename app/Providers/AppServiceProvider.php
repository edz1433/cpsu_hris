<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
    public function boot()
    {
        // Generate all URLs (routes, assets, form actions) from APP_URL so the
        // "/public" segment never leaks into links, regardless of how the
        // request was rewritten into the public/ folder.
        if ($root = config('app.url')) {
            URL::forceRootUrl($root);
        }
    }
}
