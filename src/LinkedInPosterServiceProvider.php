<?php

namespace Kistlak\LinkedinPoster;

use Illuminate\Support\ServiceProvider;

class LinkedInPosterServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        $this->loadViewsFrom(__DIR__.'/resources/views', 'linkedin-poster');
        $this->mergeConfigFrom(__DIR__ . '/config/linkedin-share.php', 'linkedin-share');

        // Allow publishing config to app's config folder
        $this->publishes([
            __DIR__ . '/config/linkedin-share.php' => config_path('linkedin-share.php'),
        ], 'linkedin-config');
    }

    public function register() {}
}
