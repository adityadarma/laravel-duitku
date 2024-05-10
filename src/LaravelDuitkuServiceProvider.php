<?php

namespace AdityaDarma\LaravelDuitku;

use AdityaDarma\LaravelDuitku\Console\LaravelDuitkuInstallCommand;
use Illuminate\Support\ServiceProvider;

class LaravelDuitkuServiceProvider extends ServiceProvider
{
    public const CONFIG_PATH = __DIR__ . '/../config/duitku.php';

    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(self::CONFIG_PATH, 'duitku');

        $this->app->bind('laravel-duitku', function() {
            return new LaravelDuitku();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                self::CONFIG_PATH => config_path('duitku.php')
            ], 'config');

            $this->commands([LaravelDuitkuInstallCommand::class]);
        }
    }
}
