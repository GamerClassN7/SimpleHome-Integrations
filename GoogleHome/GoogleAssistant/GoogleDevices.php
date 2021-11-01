<?php

namespace Modules\GoogleHome\GoogleAssistant;

use Modules\GoogleHome\GoogleAssistant\traits;
use Modules\GoogleHome\GoogleAssistant\Types;

use Illuminate\Support\Facades\Http;

class GoogleDevices
{
    public $device;

    public function __construct($device)
    {
        $this->device = $device;
    }

    public function syncTermostat()
    {

        $property = null;
        foreach ($this->device->getProperties as $property) {
            if ($property->type == "temperature_control") {
                $property  = $property;
                break;
            }
        }

        $device = [
            "id" => (string) $this->device->id,
            "type" => "action.devices.types.THERMOSTAT",
            "name" => [
                "name" => (isset($property) ? (string) $property->nick_name : "SH#" . $this->device->id),
            ],
            "traits" => [
                "action.devices.traits.TemperatureSetting"
            ],
            "attributes" => [
                "availableThermostatModes" => [
                    "off",
                    "heat"
                ],
                "thermostatTemperatureUnit"  => "C",
                "thermostatTemperatureRange" => [
                    "minThresholdCelsius" => (isset($property) ? $property->min_setting_value : 1),
                    "maxThresholdCelsius" => (isset($property) ? $property->max_setting_value : 30),
                ],
                "temperatureStepCelsius" => (isset($property) ? $property->step_setting_value : 5),
            ],
            "willReportState" => false,
            "otherDeviceIds" => [
                [
                    "deviceId" => "SH#" . $this->device->id,
                ],
            ],
            "roomHint" => (isset($property) ? (string) $property->room->name : "test"),
            "deviceInfo" => [
                "manufacturer" => $this->device->integration,
                // "model" => "hs1234",
                // "hwVersion" => "3.2",
                // "swVersion" => "11.4"
            ],
        ];

        //dd($property->max_value_setting);
        //dd($property->min_value_setting);

        return $device;
    }

    public function executeTermostat($executions)
    {
        $executionResult = [
            "online" => !$this->device->offline,
            "thermostatMode" => "off",
        ];

        $property = null;
        foreach ($this->device->getProperties as $property) {
            if ($property->type == "temperature_control") {
                $property = $property;
                break;
            }
        }

        foreach ($executions as $execution) {
            switch ($execution['command']) {
                case 'action.devices.commands.ThermostatTemperatureSetpoint':
                    if ($property->type == "temperature_control" /*&& $property->latestRecord->done = 1*/) {
                        $property->setValue((int) $execution['params']['thermostatTemperatureSetpoint'], "api");
                        $executionResult["thermostatTemperatureSetpoint"] = (int) $property->latestRecord->value;
                    }
                    break;
                case 'action.devices.commands.ThermostatSetMode':
                    if ($property->type == "temperature_control" /*&& $property->latestRecord->done = 1*/) {
                        if ($execution['params']['thermostatMode'] == "heat") {
                            $property->setValue($property->getLatestRecordNotNull()->value, "api");
                        } else {
                            $property->setValue(0, "api");
                        }
                        $executionResult["thermostatTemperatureSetpoint"] = (int) $property->getLatestRecordNotNull()->value;
                    }
                    break;
            }
        }

        if ($property->latestRecord->value > 0) {
            $executionResult["thermostatMode"] = "heat";
        } else {
            $executionResult["thermostatMode"] = "off";
        }

        if (isset($property->room->state)) {
            $roomStates = $property->room->state;
            if (isset($property->room->state['temp']) && $property->room->state['temp'] != 0) {
                $executionResult["thermostatTemperatureAmbient"] = $roomStates['temp'];
            } else {
                $executionResult["thermostatTemperatureAmbient"] = (int) $property->latestRecord->value;
            }
            if (isset($roomStates['humi']) &&  $roomStates['humi'] != 0) $executionResult["thermostatHumidityAmbient"] =  $roomStates['humi'];
        }

        return $executionResult;
    }

    public function queryTermostat()
    {
        $device = [
            "online" => !false,
        ];

        foreach ($this->device->getProperties as $property) {
            if ($property->type == "temperature_control") {
                if ($property->latestRecord->value > 0) {
                    $device["thermostatMode"] = "heat";
                } else {
                    $device["thermostatMode"] = "off";
                }
                $device["thermostatTemperatureSetpoint"] = (int) $property->latestRecord->value;
                if (isset($property->room->state['temp']) && $property->room->state['temp'] != 0) {
                    $device["thermostatTemperatureAmbient"] = (int) $property->room->state['temp'];
                } else {
                    $device["thermostatTemperatureAmbient"] = (int) $property->latestRecord->value;
                }

                if (isset($property->room->state['humi']) && $property->room->state['humi'] != 0) $device["thermostatHumidityAmbient"] = $property->room->state['humi'];
                break;
            }
        }

        if (!$this->device->offline) {
            $device["status"] = "SUCCESS";
        }

        return $device;
    }

    public function syncRelay()
    {
        $property = null;
        foreach ($this->device->getProperties as $property) {
            if ($property->type == "relay") {
                $property = $property;
                break;
            }
        }

        if ($property == null) return null;

        $device = [
            "id" => (string) $this->device->id,
            "type" => "action.devices.types.SWITCH",
            "name" => [
                "name" => (isset($property) ? (string) $property->nick_name : "SH#" . $this->device->id),
            ],
            "traits" => [
                "action.devices.traits.OnOff",
            ],
            "attributes" => [
                "commandOnlyOnOff"  => false
            ],
            "willReportState" => false,
            "otherDeviceIds" => [
                [
                    "deviceId" => "SH#" . $this->device->id,
                ],
            ],
            "roomHint" => (isset($property) ? (string) $property->room->name : "test"),
            "deviceInfo" => [
                "manufacturer" => (string) $this->device->integration,
                //"model" => "hs1234",
                //"hwVersion" => "3.2",
                //"swVersion" => "11.4"
            ],
        ];
        return $device;
    }

    public function queryRelay()
    {
        $device = [
            "online" => !$this->device->offline,
            "status" => ($this->device->offline ? 'OFFLINE' : 'SUCCESS'),
            "on" => false,
        ];

        foreach ($this->device->getProperties as $property) {
            if ($property->type == "relay" && $property->latestRecord->value == 1) {
                $device["on"] = true;
            }
        }

        if ($this->device->offline) {
            $device["status"] = "SUCCESS";
        }

        return $device;
    }

    public function executeRelay($executions)
    {
        $executionResult = [
            "online" => !$this->device->offline,
        ];

        foreach ($executions as $execution) {
            switch ($execution['command']) {
                case 'action.devices.commands.OnOff':
                    foreach ($this->device->getProperties as $property) {
                        if ($property->type == "relay" /*&& $property->latestRecord->done = 1*/) {
                            $property->setValue($execution['params']['on'], "api");
                            $executionResult["on"] = ($property->latestRecord->value == 1 ? true : false);
                            break;
                        }
                    }
                    break;
            }
        }

        return $executionResult;
    }

    public function syncSensor()
    {
        $device = [
            "id" => (string) $this->device->id,
            "type" => "action.devices.types.SENSOR",
            "name" => [
                "name" => (string) "SH#" . $this->device->id,
            ],
            "traits" => [
                "action.devices.traits.TemperatureSetting"
            ],
            "attributes" => [
                "availableThermostatModes" => [
                    "off",
                    "heat"
                ],
                "thermostatTemperatureUnit"  => "C"
            ],
            "willReportState" => false,
            "otherDeviceIds" => [
                [
                    "deviceId" => "SH#" . $this->device->id,
                ],
            ],
            "roomHint" => "test",
            "deviceInfo" => [
                "manufacturer" => $this->device->integration,
                // "model" => "hs1234",
                // "hwVersion" => "3.2",
                // "swVersion" => "11.4"
            ],
        ];
        return $device;
    }

    public function syncDoor()
    {
        $device = [
            "id" => (string) $this->device->id,
            "type" => "action.devices.types.DOOR",
            "name" => [
                "name" => (string) "SH#" . $this->device->id,
            ],
            "traits" => [
                "action.devices.traits.OpenClose"
            ],
            "willReportState" => false,
            "otherDeviceIds" => [
                "deviceId" => "SH#" . $this->device->id,
            ],
            "roomHint" => "test",
            "deviceInfo" => [
                "manufacturer" => $this->device->integration,
                // "model" => "hs1234",
                // "hwVersion" => "3.2",
                // "swVersion" => "11.4"
            ],
        ];
        return $device;
    }

    //Not Finished
    public function syncMedia()
    {
        $device = [
            "id" => (string) $this->device->id,
            "type" => "action.devices.types.REMOTECONTROL",
            "name" => [
                "name" => (string) "SH#" . $this->device->id,
            ],
            "traits" => [
                "action.devices.traits.AppSelector",
                "action.devices.traits.InputSelector",
                "action.devices.traits.MediaState",
                "action.devices.traits.OnOff",
                "action.devices.traits.TransportControl",
                "action.devices.traits.Volume"
            ],
            "willReportState" => false,
            "attributes" => [
                "transportControlSupportedCommands" => [
                    "NEXT",
                    "PREVIOUS",
                    "PAUSE",
                    "STOP",
                    "RESUME",
                    "CAPTION_CONTROL"
                ],
            ],
            "otherDeviceIds" => [
                "deviceId" => "SH#" . $this->device->id,
            ],
            "roomHint" => "test",
            "deviceInfo" => [
                "manufacturer" => $this->device->integration,
                // "model" => "hs1234",
                // "hwVersion" => "3.2",
                // "swVersion" => "11.4"
            ],
        ];
        return $device;
    }

    public function getDeviceSyncPayload()
    {
        $deviceType = 'sync' . ucfirst($this->device->type);
        if (!method_exists($this, $deviceType)) {
            return false;
        }
        return $this->$deviceType();
    }

    public function getDeviceQueryPayload()
    {
        $deviceType = 'query' . ucfirst($this->device->type);
        if (!method_exists($this, $deviceType)) {
            return false;
        }
        return $this->$deviceType();
    }

    public function getDeviceExecutePayload($executions)
    {
        $deviceType = 'execute' . ucfirst($this->device->type);
        if (!method_exists($this, $deviceType)) {
            return false;
        }
        return $this->$deviceType($executions);
    }
}
