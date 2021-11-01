<?php

namespace Modules\GoogleHome\GoogleAssistant;

class Traits
{
    public static $types = [
        "RELAY" => "action.devices.traits.OnOff",
        "ON_OFF" => "action.devices.traits.OnOff",
        "SWITCH" => "action.devices.traits.OnOff",
        "TEMP_CONT" => "action.devices.traits.TemperatureSetting",
        "CONTROL_TEMP" => "action.devices.traits.TemperatureSetting",
    ];

    public static function getTraits($constant)
    {
        if (isset(self::$types[$constant])) {
            return self::$types[$constant];
        }
        return false;
    }
}
