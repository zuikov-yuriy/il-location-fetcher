<?php

namespace TheP6\ILLocationFetcher\Providers;

use Illuminate\Support\ServiceProvider;
use TheP6\ILLocationFetcher\Console\FetchLocationsCommand;
use TheP6\ILLocationFetcher\Console\InstallCommand;

class ILLocationFetcherProvider extends ServiceProvider
{

    public function boot()
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            InstallCommand::class,
            FetchLocationsCommand::class,
        ]);
    }

    public function register() {
        $this->offerPublishing();
    }

    /**
     * Setup the resource publishing groups for RabbitEvents.
     *
     * @return void
     */
    protected function offerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/il_location_fetch.php' => $this->app->basePath('config/il_location_fetch.php'),
            ], 'il-location-fetcher-resource');
        }
    }
}