<?php

namespace Modules\GoogleHome\GoogleAssistant;

class Traids
{
    public static $types = [
        "RELAY" => "action.devices.traits.OnOff",
        "ON_OFF" => "action.devices.traits.OnOff",
        "SWITCH" => "action.devices.traits.OnOff",
        "TEMP_CONT" => "action.devices.traits.TemperatureSetting",
        "CONTROL_TEMP" => "action.devices.traits.TemperatureSetting",
    ];

    public static function getTraid($constant)
    {
        if (isset(self::$types[$constant])) {
            return self::$types[$constant];
        }
        return false;
    }
}
