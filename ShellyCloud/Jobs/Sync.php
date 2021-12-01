<?php

namespace Modules\ShellyCloud\Jobs;

use App\Helpers\SettingManager;
use App\Models\Devices;
use App\Models\Properties;
use App\Models\Rooms;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;


class sync implements ShouldQueue
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
        $response = Http::withHeaders([
            "Content-Type" => "application/x-www-form-urlencoded",
        ])->get('https://' . SettingManager::get("apiServerLocation", "shellycloud")->value . '.cloud/interface/device/get_all_lists', [
            'auth_key' => SettingManager::get("apiToken", "shellycloud")->value
        ]);

        if ($response->ok()) {
            /*Room Sync*/
            $roomsList = $response->json()["data"]["rooms"];
            if (SettingManager::get("syncRooms", "shellycloud")->value) {
                foreach ($roomsList as $key => $ShellyRoom) {
                    if (Rooms::where('name', Str::lower($ShellyRoom["name"]))->count() == 0) {
                        $room = new Rooms();
                        $room->name = $ShellyRoom["name"];
                        $room->save();
                    }
                }
            }

            /*Device Sync*/
            foreach ($response->json()["data"]["devices"] as $key => $ShellyDevice) {
                $roomSlug = Str::lower($roomsList[$ShellyDevice["room_id"]]["name"]);
                $roomId = Rooms::where('name', Str::lower($roomSlug))->first()->id;

                if (Devices::where('token', Str::lower($ShellyDevice["id"]))->count() == 0) {
                    $device = new Devices();
                    $device->token = $ShellyDevice["id"];
                    $device->hostname = $ShellyDevice["name"];
                    $device->integration = "shellyCloud";
                    $device->type = $ShellyDevice["category"];
                    $device->approved = 0;
                    $device->save();
                    //TODO: Fill in creation date
                } elseif ($device = Devices::where('token', Str::lower($ShellyDevice["id"]))->First()) {
                    $device->hostname = $ShellyDevice["name"];
                    $device->save();
                }

                if (Properties::where('nick_name', "shellycloud." . $ShellyDevice["name"] . ":rssi")->count() == 0) {
                    $property = new Properties();
                    $property->device_id = $device->id;
                    $property->room_id = $roomId;
                    $property->nick_name = "shellycloud." . $ShellyDevice["name"] . ".rssi";
                    $property->icon = "fa-wifi";
                    $property->type = "wifi";
                    $property->save();
                }

                if (Properties::where('nick_name', "shellycloud." .  $ShellyDevice["name"] . ":power")->count() == 0) {
                    $property = new Properties();
                    $property->device_id = $device->id;
                    $property->room_id = $roomId;
                    $property->nick_name = "shellycloud." . $ShellyDevice["name"] . ".power";
                    $property->icon = "fa-bolt";
                    $property->type = "power";
                    $property->units = "W";
                    $property->save();
                }

                for ($i = 1; $i <= $ShellyDevice["channels_count"]; $i++) {
                    if (Properties::where('nick_name', "shellycloud." . $ShellyDevice["name"] . ":relay_" . $i)->count() == 0) {
                        $property = new Properties();
                        $property->device_id = $device->id;
                        $property->room_id = $roomId;
                        $property->nick_name = "shellycloud." . $ShellyDevice["name"] . ".relay_" . $i;
                        $property->icon = "fa-wifi";
                        $property->type = $ShellyDevice["category"];

                        $property->save();
                    } elseif ($property = Properties::where('nick_name', "shellycloud." . $ShellyDevice["name"] . 'relay_' . $i)->First()) {
                        $property->room_id = $roomId;
                        $property->save();
                    }
                }
            }
        }
    }
}
