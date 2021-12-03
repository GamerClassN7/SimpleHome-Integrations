<?php

namespace Modules\Sun\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

use Modules\Sun\Jobs\DiscoveryJob;
use Modules\Sun\Jobs\SynchronizationJob;

class SunServiceProvider extends ServiceProvider
{
    /**
     * @var string $moduleName
     */
    protected $moduleName = 'Sun';

    /**
     * @var string $moduleNameLower
     */
    protected $moduleNameLower = 'sun';

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerConfig();
        $this->registerCommands();

        (new DiscoveryJob)->handle();
        (new SynchronizationJob)->handle();

        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            #$schedule->command('simplehome:shellycloud:sync')->hourly();
            #$schedule->command('simplehome:shellycloud:fetch')->cron("* * * * *");
        });
    }

    public function registerCommands()
    {
        $this->commands([]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            module_path($this->moduleName, 'Config/config.php') => config_path($this->moduleNameLower . '.php'),
        ], 'config');
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'),
            $this->moduleNameLower
        );
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
