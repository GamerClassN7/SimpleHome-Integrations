<?php

namespace Modules\ShellyCloud\Jobs;

use App\Helpers\SettingManager;
use App\Models\Devices;
use App\Models\Properties;
use App\Models\Records;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

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
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //API Rate Limit
        // if ($device->heardbeath->diffInSeconds() <= 1) {
        //     return false;
        // }

        $response = Http::asForm()->post('https://' . SettingManager::get("apiServerLocation", "shellycloud")->value . '.cloud/device/all_status', [
            'auth_key' => SettingManager::get("apiToken", "shellycloud")->value
        ]);

        if ($response->ok() === false) {
            return;
        }
v
        foreach ($response->json()["data"]["devices_status"]  as $device_token => $device_status) {
            $device = Devices::where('token', Str::lower($device_token))->First();
            if ($device === false) {
                return false;
            }
            $device->setHeartbeat();

            if (!$device->approved) {
                return false;
            }

            if (isset($device_status["relays"]) && count($device_status["relays"]) > 0) {
                foreach ($device_status["relays"] as $key => $relay) {
                    $pseudoId = (int) $key;
                    $property = Properties::where('nick_name', $device->hostname . ':relay_' . ($pseudoId + 1))->First();
                    if (!isset($property->last_value->value) || $property->last_value->value != (int) $relay["ison"]) {
                        $record = new Records();
                        $record->property_id = $property->id;
                        $record->value = (int) $relay["ison"];
                        $record->done = true;
                        $record->save();
                    }
                }
            }

            //TODO: Additional Values
            // if (isset($device_status["meters"]) && count($device_status["meters"]) > 0) {
            //     foreach ($device_status["meters"] as $key => $meter) {
            //         //$meter_type =
            //         $property = Properties::where('nick_name', $device->hostname . ':' . $meter_key)->First();
            //         if (!isset($property->last_value->value) || $property->last_value->value != (int) $device_status["meters"][0][$meter_key]) {
            //             $record = new Records();
            //             $record->property_id = $property->id;
            //             $record->value = (int) $device_status["meters"][0][$meter_key];
            //             $record->done = true;
            //             $record->save();
            //         }
            //     }
            // }
            //TODO: Fetch other non settable values
            /*
                            "meters": [
                                {
                                    "power": 31.13,
                                    "overpower": 0,
                                    "is_valid": true,
                                    "timestamp": 1631281560,
                                    "counters": [
                                        53.646,
                                        55.928,
                                        66.694
                                    ],
                                    "total": 64855
                                }
                            ],
                            */

            $property = Properties::where('nick_name', $device->hostname . ':rssi')->First();
            if (!isset($property->last_value->value) || $property->last_value->value != $device_status["wifi_sta"]["rssi"]) {
                $record = new Records();
                $record->property_id = $property->id;
                $record->value = $device_status["wifi_sta"]["rssi"];
                $record->done = true;
                $record->save();
            }
        }

        return true;
    }
}
