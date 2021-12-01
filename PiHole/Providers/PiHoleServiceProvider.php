<?php

namespace Modules\PiHole\Providers;

use App\Helpers\SettingManager;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\ServiceProvider;
use Modules\PiHole\Jobs\Fetch;

class PiHoleServiceProvider extends ServiceProvider
{
    /**
     * @var string $moduleName
     */
    protected $moduleName = 'PiHole';

    /**
     * @var string $moduleNameLower
     */
    protected $moduleNameLower = 'pihole';

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerConfig();
        $this->registerSettings();

        // (new Fetch)->handle();

        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->job(new Fetch())->withoutOverlapping()->everyFiveMinutes();
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
    }

    public function registerSettings()
    {
        $index = "ipAddress";
        if (SettingManager::get($index, $this->moduleNameLower) == null) {
            SettingManager::register($index, true, 'string', $this->moduleNameLower);
        }
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
