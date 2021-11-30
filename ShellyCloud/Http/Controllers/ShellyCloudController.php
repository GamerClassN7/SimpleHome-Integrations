<?php

namespace Modules\ShellyCloud\Http\Controllers;

use App\Models\Devices;
use App\Models\Properties;
use App\Models\Records;
use Illuminate\Contracts\Support\Renderable;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ShellyCloud\DeviceTypes\ShellyOnOff;

class ShellyCloudController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function set($id, $state)
    {
        $property = Properties::find($id);
        $cannel = 0;

        foreach ($property->device->properties->where("type", $property->type) as $deviceProperty) {
            if ($property->type == $deviceProperty->type) {
                break;
            }
            $cannel++;
        }

        $propertyToken = $property->device->token;

        $record                 = new Records;
        $record->value          = $state;
        $record->property_id    = $id;
        $record->save();

        $response = ShellyOnOff::setState($propertyToken, ($state == 1 ? "on" : "off"),);

        if ($response) {
            return response()->json([
                "value" => $record->value,
                "icon" => ($record->value == "1" ? "<i class=\"fas fa-toggle-on text-primary\"></i>" : "<i class=\"fas fa-toggle-off\"></i>"),
                "url" => route('shellycloud.set', ['properti_id' => $record->property_id, 'value' => (int) !$record->value]),
            ]);
        }

        return response()->json([
            "value" => $state,
            "icon" => ($state == "1" ? "<i class=\"fas fa-toggle-on text-primary\"></i>" : "<i class=\"fas fa-toggle-off\"></i>"),
            "url" => route('shellycloud.set', ['properti_id' => $record->property_id, 'value' => (int) !$state]),
        ]);
    }

    public function reboot($id)
    {
        $propertyToken = Devices::find($id)->token;

        ShellyOnOff::reboot($propertyToken);

        return 'true';
    }
}
