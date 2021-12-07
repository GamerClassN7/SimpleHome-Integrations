<?php

namespace Modules\ShellyCloud\Providers;

use App\Helpers\SettingManager;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Factory;

use Illuminate\Support\ServiceProvider;

use Modules\ShellyCloud\Console\FetchCommand;
use Modules\ShellyCloud\Console\SyncCommand;

use Modules\ShellyCloud\Jobs\Fetch;
use Modules\ShellyCloud\Jobs\Sync;


class ShellyCloudServiceProvider extends ServiceProvider
{
    /**
     * @var string $moduleName
     */
    protected $moduleName = 'ShellyCloud';

    /**
     * @var string $moduleNameLower
     */
    protected $moduleNameLower = 'shellycloud';

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerSettings();
        $this->registerConfig();
        $this->registerCommands();

        //Sync::handle();
        //Fetch::handle();

        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('simplehome:shellycloud:sync')->hourly();
            $schedule->command('simplehome:shellycloud:fetch')->cron("* * * * *");
        });
    }

    public function registerSettings()
    {
        $index = "syncRooms";
        if (SettingManager::get($index, $this->moduleNameLower) == null) {
            SettingManager::register($index, true, 'bool', $this->moduleNameLower);
        }

        $index = "apiToken";
        if (SettingManager::get($index, $this->moduleNameLower) == null) {
            SettingManager::register($index, "", 'string', $this->moduleNameLower);
        }

        $index = "apiServerLocation";
        if (SettingManager::get($index, $this->moduleNameLower) == null) {
            SettingManager::register($index, "", 'string', $this->moduleNameLower);
        }

        $index = "pasivMode";
        if (SettingManager::get($index, $this->moduleNameLower) == null) {
            SettingManager::register($index, false, 'bool', $this->moduleNameLower);
        }
    }

    public function registerCommands()
    {
        $this->commands([
            fetchCommand::class,
            syncCommand::class,
        ]);
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
