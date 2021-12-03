<?php

namespace Modules\Sun\Jobs;

use App\Helpers\SettingManager;

use App\Models\Devices;
use App\Models\Properties;
use App\Models\Rooms;



use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;


class DiscoveryJob implements ShouldQueue
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
        $token = Str::lower(md5("sun"));
        $device = Devices::where('token', $token)->First();

        if (null == $device) {
            $this->createDevice($token, 'sun', 'sun');
            return false;
        }

        $device->setHeartbeat();
        $defaultRoomId = Rooms::where('default', true)->first()->id;
        $property = Properties::where('type', 'state')->where('device_id', $device->id)->First();

        if (null == $property) {
            $this->createProperti($device->id, $defaultRoomId, 'sun_state', "fa-sun", 'state');
            return true;
        }
        return true;
    }

    private function createDevice($token, $hostname, $type)
    {
        $device = new Devices();
        $device->token = $token;
        $device->hostname = $hostname;
        $device->integration = "sun";
        $device->type = $type;
        $device->approved = 1;
        $device->save();
        return $device;
    }

    private function createProperti($deviceId, $defaultRoomId, $metricsFriendlyName, $metricIcon, $type)
    {
        $property = new Properties();
        $property->device_id = $deviceId;
        $property->room_id = $defaultRoomId;
        $property->is_hidden = True;
        $property->nick_name = "sun.state";
        $property->icon = $metricIcon;
        $property->type = $type;
        $property->save();
    }
}
