<?php

namespace Modules\OpenWeatherMap\Jobs;

use App\Helpers\SettingManager;
use App\Models\Devices;
use App\Models\Properties;
use App\Models\Records;
use App\Models\Rooms;

use Illuminate\Bus\Queueable;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\OpenWeatherMap\Jobs\Fetch;

class fetch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //Translate Humidity + Presure
        $metrics = [
            "main" => [
                "temp",
                "humidity",
                "pressure",
            ],
        ];

        $metricsIcons = [
            "main" => [
                "fa-thermometer-empty",
                "fa-tint",
                "fa-tachometer-alt",
            ],
        ];

        $metricsFriendlyName = [
            "main" => [
                "Temperature",
                "Humidity",
                "Presures",
            ],
        ];

        $token = Str::lower(md5("openweathermap"));
        $defaultRoom = Rooms::where('default', true)->first()->id;
        $device = Devices::where('token', $token)->where('integration', "openweathermap")->First();

        if (null == $device) {
            $device = $this->createDevice($token);
            return false;
        }


        if (!$device->approved) {
            $device->setHeartbeat();
            return false;
        }

        $response = Http::withHeaders([])->get('api.openweathermap.org/data/2.5/weather?q=' . SettingManager::get("city", "openweathermap")->value . '&appid=' . SettingManager::get("apiToken", "openweathermap")->value . '&units=metric');
        if ($response->ok() === false) {
            return;
        }
        $device->setHeartbeat();

        $jsonResponse = $response->json();
        foreach ($metrics["main"] as $metric_key => $metric) {
            if (!isset($jsonResponse["main"][$metric])) {
                continue;
            }

            $property = Properties::where('type', $this->getMetricSlug($metricsFriendlyName["main"][$metric_key]))->where('device_id', $device->id)->First();
            if (null == $property) {
                $property = $this->createProperti($device->id, $defaultRoom, $metricsFriendlyName["main"][$metric_key], $metricsIcons["main"][$metric_key], $metricsFriendlyName["main"][$metric_key]);
            }
            $this->createRecord($property->id, $jsonResponse["main"][$metric]);
        }

        return true;
    }

    private function getMetricSlug($metricCode)
    {
        $metricsSlugs = [
            "humidity" => "humi",
            "pressure" => "press",
        ];

        if (!in_array(Str::lower($metricCode), $metricsSlugs)) {
            return $metricCode;
        }

        return $metricsSlugs[$metricCode];
    }

    private function createProperti($deviceId, $defaultRoomId, $metricsFriendlyName, $metricIcon, $metric)
    {
        $property = new Properties();
        $property->device_id = $deviceId;
        $property->room_id = $defaultRoomId;
        $property->nick_name = "openweathermap." . $metricsFriendlyName;
        $property->icon = $metricIcon;
        $property->type = $this->getMetricSlug($metric);
        $property->save();
        return $property;
    }

    private function createDevice($token)
    {
        $device = new Devices();
        $device->token = $token;
        $device->hostname = "openweathermap";
        $device->integration = "openweathermap";
        $device->type = "custome";
        $device->approved = 0;
        $device->sleep = 300000;
        $device->save();
        return $device;
    }

    private function createRecord($propertyId, $value)
    {
        $record = new Records();
        $record->property_id = $propertyId;
        $record->value = (int) round($value);
        $record->origin = 'module:openweathermap';
        $record->done = true;
        $record->save();
        return $record;
    }
}
