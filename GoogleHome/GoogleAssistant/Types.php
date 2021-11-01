<?php

namespace Modules\GoogleHome\GoogleAssistant;

class Types
{
    public static $types = [
        "TERMOSTAT" => "action.devices.types.THERMOSTAT",
        "RELAY" => "action.devices.types.SWITCH",
    ];

    public static function getType($constant)
    {
        if (isset(self::$types[$constant])) {
            return self::$types[$constant];
        }
        return false;
    }
}
