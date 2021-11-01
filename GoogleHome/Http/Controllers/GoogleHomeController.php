<?php

namespace Modules\GoogleHome\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Models\Devices;
use Modules\GoogleHome\GoogleAssistant;
use Modules\GoogleHome\GoogleAssistant\GoogleDevices;
use Illuminate\Support\Facades\Log;

class GoogleHomeController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function auth()
    {
        //return redirect()->route('oauth.login');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function fulfillment(Request $request)
    {
        Log::info($request);

        $response = [
            "requestId" => $request->requestId,
            "payload" => [],
        ];

        foreach ($request->inputs as $input) {
            $action = strtolower(explode(".", $input['intent'])[2]) . "Devices";
            switch (strtolower(explode(".", $input['intent'])[2])) {
                case 'sync':
                    $response['payload']['devices'] = $this->$action();
                    $response['payload']['agentUserId'] = (string) $request->user()->id;
                    break;

                case 'query':
                    $response['payload']['devices'] = $this->$action($input['payload']);
                    break;

                case 'execute':
                    $response['payload']['commands'] = $this->$action($input['payload']);
                    break;
            }
        }

        Log::info($response);

        return response()->json($response);
    }

    private function syncDevices($payload = "")
    {
        $response = [];
        $devices = Devices::all();
        foreach ($devices as $device) {
            $googleDevice = new GoogleDevices($device);
            if (($payload = $googleDevice->getDeviceSyncPayload())) {
            $response[] = $payload;
            }
        }
        return $response;
    }

    private function queryDevices($payload)
    {
        $response = [];
        foreach ($payload['devices'] as $device) {
            $device = Devices::find($device["id"]);
            if (empty($device)) continue;
            $googleDevice = new GoogleDevices($device);
            if (($payload = $googleDevice->getDeviceQueryPayload())) {
                $response[$device->id] = $payload;
            }
        }
        return $response;
    }

    private function executeDevices($payload)
    {
        $response = [];
        foreach ($payload['commands'] as $command) {
            $commandPayload = [];

            foreach ($command['devices'] as $device) {
                $device = Devices::find($device["id"]);
                if (empty($device)) continue;
                $commandPayload["ids"][] = (string) $device->id;
                $googleDevice = new GoogleDevices($device);
                if (($payload = $googleDevice->getDeviceExecutePayload($command['execution']))) {
                    $commandPayload["status"] = "SUCCESS";
                    $commandPayload["states"] = $payload;
                }
            }

            $response[] = $commandPayload;
        }
        return $response;
    }
}
