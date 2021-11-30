<?php

namespace Modules\ShellyCloud\Providers;

use App\Helpers\SettingManager;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Factory;

use Illuminate\Support\ServiceProvider;
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
        $this->registerTranslations();
        $this->registerConfig();

        Fetch::handle();

        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->job(new Sync())->hourly();
            $schedule->job(new Fetch())->cron("* * * * *");
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
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/' . $this->moduleNameLower);

        $sourcePath = module_path($this->moduleName, 'Resources/views');

        $this->publishes([
            $sourcePath => $viewPath
        ], ['views', $this->moduleNameLower . '-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/' . $this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
        } else {
            $this->loadTranslationsFrom(module_path($this->moduleName, 'Resources/lang'), $this->moduleNameLower);
        }
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

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (\Config::get('view.paths') as $path) {
            if (is_dir($path . '/modules/' . $this->moduleNameLower)) {
                $paths[] = $path . '/modules/' . $this->moduleNameLower;
            }
        }
        return $paths;
    }
}
