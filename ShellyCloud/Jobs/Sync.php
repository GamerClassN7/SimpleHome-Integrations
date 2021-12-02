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
            $this->roomsSynchronization($roomsList);

            /*Device Sync*/
            $this->devicesSynchronization($response->json()["data"]["devices"], $roomsList);
        }
        return true;
    }

    private function createProperty($device, $type, $roomId, $icon = "default", $units = "", $iterator = null)
    {
        $property = new Properties();
        $property->device_id = $device->id;
        $property->room_id = $roomId;
        $property->nick_name = "shellycloud." . $device->hostname . "." . ($type == "wifi" ? "rssi" : "") . ($iterator == null ? "" : ("_"  . $iterator));
        $property->icon = "fa-wifi";
        $property->type = $type;
        $property->save();
        return $property;
    }

    private function createDevice($token, $hostname, $type)
    {
        $device = new Devices();
        $device->token = $token;
        $device->hostname = $hostname;
        $device->integration = "shellyCloud";
        $device->type = $type;
        $device->approved = 0;
        $device->save();
        return $device;
    }

    private function roomsSynchronization($roomsList)
    {
        if (SettingManager::get("syncRooms", "shellycloud")->value) {
            foreach ($roomsList as $ShellyRoom) {
                if (Rooms::where('name', Str::lower($ShellyRoom["name"]))->count() == 0) {
                    $room = new Rooms();
                    $room->name = $ShellyRoom["name"];
                    $room->save();
                }
            }
        }
    }

    private function devicesSynchronization($deviceList, $roomsList)
    {
        foreach ($deviceList as $key => $ShellyDevice) {
            $roomSlug = Str::lower($roomsList[$ShellyDevice["room_id"]]["name"]);
            $roomId = Rooms::where('name', Str::lower($roomSlug))->first()->id;

            if (Devices::where('token', Str::lower($ShellyDevice["id"]))->count() == 0) {
                $device = $this->createDevice($ShellyDevice["id"], $ShellyDevice["name"], $ShellyDevice["category"]);
                //TODO: Fill in creation date
            } elseif ($device = Devices::where('token', Str::lower($ShellyDevice["id"]))->First()) {
                $device->hostname = $ShellyDevice["name"];
                $device->save();
            }

            $this->propertiesSynchronization($ShellyDevice, $device, $roomId);
        }
    }

    private function propertiesSynchronization($deviceData, $device, $roomId)
    {
        if (Properties::where('nick_name', "shellycloud." . $deviceData["name"] . ".rssi")->count() == 0) {
            $this->createProperty($device, "wifi", $roomId, "fa-wifi", "dbm");
        }

        if (Properties::where('nick_name', "shellycloud." .  $deviceData["name"] . ".power")->count() == 0) {
            $this->createProperty($device, "power", $roomId, "fa-bolt", "W");
        }

        for ($i = 1; $i <= $deviceData["channels_count"]; $i++) {
            if (Properties::where('nick_name', "shellycloud." . $deviceData["name"] . ".relay_" . $i)->count() == 0) {
                $this->createProperty($device, "relay", $roomId, "fa-on-off", "", $i);
            } elseif ($property = Properties::where('nick_name', "shellycloud." . $deviceData["name"] . 'relay_' . $i)->First()) {
                $property->room_id = $roomId;
                $property->save();
            }
        }
    }
}
