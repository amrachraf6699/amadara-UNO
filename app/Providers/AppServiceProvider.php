<?php

namespace App\Providers;

use App\Services\GeminiAiService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('gemini-ai', fn () => new GeminiAiService());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Replace the package's 30-second default text client after package
        // providers have registered their binding.
        $this->app->extend('gemini-ai', fn () => new GeminiAiService());
    }
}
