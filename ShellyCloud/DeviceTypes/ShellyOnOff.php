<?php

namespace Modules\ShellyCloud\DeviceTypes;

use App\Helpers\SettingManager;
use Illuminate\Support\Facades\Http;

class ShellyOnOff
{
     public static function setState($id, $state, $channel = 0)
     {
          if (SettingManager::get("pasivMode", "shellycloud")->value) {
               return "False";
          }


          $response = Http::asForm()->withHeaders([
               "Authorization" => "Bearer " . SettingManager::get("apiToken", "shellycloud")->value,
               "Content-Type" => "application/x-www-form-urlencoded",
          ])->post('https://' . SettingManager::get("apiServerLocation", "shellycloud")->value . '.cloud/device/relay/control', [
               "channel" => $channel,
               "turn" => $state,
               "id" => $id,
          ]);

          if ($response->ok()) {
               return True;
          }

          return False;
     }

     public static function reboot($id)
     {
          if (SettingManager::get("pasivMode", "shellycloud")->value) {
               return False;
          }

          $response = Http::asForm()->withHeaders([
               "Authorization" => "Bearer " . SettingManager::get("apiToken", "shellycloud")->value,
               "Content-Type" => "application/x-www-form-urlencoded",
          ])->post('https://' . SettingManager::get("apiServerLocation", "shellycloud")->value . '.cloud//device/reboot', [
               "id" => $id,
          ]);
          if ($response->ok()) {
               return True;
          }
          return False;
     }
}
