<?php

namespace Modules\OpenWeatherMap\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use Modules\OpenWeatherMap\Jobs\Fetch;

use App\Helpers\SettingManager;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

use App\Models\Devices;
use App\Models\Properties;
use App\Models\Records;
use App\Models\Rooms;

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
                "fas fa-list",
                "fas fa-chart-pie",
                "fas fa-hand-paper",
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
        
        $device = Devices::where('token', $token)->First();
        if ($device !== null) {
            $device->setHeartbeat();
            
            if (!$device->approved) {
                return false;
            }
            
            $response = Http::withHeaders([])->get('api.openweathermap.org/data/2.5/weather?q=' . SettingManager::get("city", "openweathermap")->value . '&appid=' . SettingManager::get("apiToken", "openweathermap")->value . '&units=metric');
            if ($response->ok() && $response->json()) {
                $jsonResponse = $response->json();
                
                foreach ($metrics["main"] as $metric_key => $metric) {
                    if (!isset($jsonResponse["main"][$metric])) {
                        continue;
                    }
                    
                    $property = Properties::where('type', $this->getMetricSlug($metric))->where('device_id', $device->id)->First();
                    if ($property == null) {
                        $property = new Properties();
                        $property->device_id = $device->id;
                        $property->room_id = $defaultRoom;
                        $property->nick_name = "openweathermap".$metricsFriendlyName["main"][$metric_key];
                        $property->icon = $metricsIcons["main"][$metric_key];
                        $property->type = $this->getMetricSlug($metric);
                        $property->save();
                    }
                    
                    $record = new Records();
                    $record->property_id = $property->id;
                    $record->value = (int) round($jsonResponse["main"][$metric]);
                    $record->done = true;
                    $record->save();
                }
            }
        } else {
            $device = new Devices();
            $device->token = $token;
            $device->hostname = "openweathermap";
            $device->type = "custome";
            $device->approved = 0;
            $device->sleep = 300000;
            $device->save();
        } 
        return true;
    } 

    private function getMetricSlug($metricCode){
        $metricsSlugs = [
            "humidity" => "humi"
        ];

        if (!in_array($metricCode, $metricSlugs)){
            return $metricCode;
        }

        return $metricsSlugs[$metricCode];

    }
} 
