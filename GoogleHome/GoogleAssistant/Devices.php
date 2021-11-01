<?php

namespace Modules\GoogleHome\GoogleAssistant;

use Modules\GoogleHome\GoogleAssistant\Traids;
use Modules\GoogleHome\GoogleAssistant\Types;

class Devices
{
    public $device = $null;

    public function __construct($device)
    {
        $device = $device;
    }

    public function termostat()
    {
        $device = [
            "id" => $this->device->id,
            "type" => "action.devices.types.THERMOSTAT",
            "name" => [
                "name" => $this->device->host_name,
            ],
            "traids" => [
                "action.devices.traits.TemperatureSetting"
            ],
            "otherDeviceIds" => [
                "deviceId" => "SH#" . $this->device->id,
            ],
            "roomHint" => "test",
            "deviceInfo" => [
                "manufacturer" => $this->device->integration,
                "model" => "hs1234",
                "hwVersion" => "3.2",
                "swVersion" => "11.4"
            ],
        ];
        return $device;
    }

    public function relay()
    {
        $device = [
            "id" => $this->device->id,
            "type" => "action.devices.types.SWITCH",
            "name" => [
                "name" => $this->device->host_name,
            ],
            "traids" => [
                "action.devices.traits.OnOff",
            ],
            "otherDeviceIds" => [
                "deviceId" => "SH#" . $this->device->id,
            ],
            "roomHint" => "test",
            "deviceInfo" => [
                "manufacturer" => $this->device->integration,
                "model" => "hs1234",
                "hwVersion" => "3.2",
                "swVersion" => "11.4"
            ],
        ];
        return $device;
    }
}
