<?php

namespace Modules\Mqtt\Providers;

use App\Helpers\SettingManager;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\ServiceProvider;

class MqttServiceProvider extends ServiceProvider
{
    /**
     * @var string $moduleName
     */
    protected $moduleName = 'Mqtt';

    /**
     * @var string $moduleNameLower
     */
    protected $moduleNameLower = 'mqtt';

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerSettings();
        $this->registerConfig();
    }

    public function registerSettings()
    {
        $index = "server";
        if (SettingManager::get($index, $this->moduleNameLower) == null) {
            SettingManager::register($index, true, 'string', $this->moduleNameLower);
        }
        $index = "port";
        if (SettingManager::get($index, $this->moduleNameLower) == null) {
            SettingManager::register($index, true, 'int', $this->moduleNameLower);
        }
        $index = "username";
        if (SettingManager::get($index, $this->moduleNameLower) == null) {
            SettingManager::register($index, true, 'string', $this->moduleNameLower);
        }
        $index = "password";
        if (SettingManager::get($index, $this->moduleNameLower) == null) {
            SettingManager::register($index, true, 'string', $this->moduleNameLower);
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
}
