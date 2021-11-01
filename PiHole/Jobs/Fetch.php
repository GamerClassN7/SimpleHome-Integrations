<?php

namespace Modules\PiHole\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Str;

use App\Models\Devices;
use App\Models\Properties;
use App\Models\Records;
use App\Models\Rooms;


class fetch implements ShouldQueue
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
        
        $test_Json = json_decode('{
            "domains_being_blocked": 125253,
            "dns_queries_today": 9062,
            "ads_blocked_today": 1517,
            "ads_percentage_today": 16.740234,
            "unique_domains": 4759,
            "queries_forwarded": 6947,
            "queries_cached": 598,
            "clients_ever_seen": 12,
            "unique_clients": 8,
            "dns_queries_all_types": 9062,
            "reply_NODATA": 63,
            "reply_NXDOMAIN": 480,
            "reply_CNAME": 3571,
            "reply_IP": 4638,
            "privacy_level": 0,
            "status": "enabled",
            "gravity_last_updated": {
                "file_exists": true,
                "absolute": 1582388108,
                "relative": {
                    "days": "5",
                    "hours": "20",
                    "minutes": "18"
                }
            }
        }', true);
        
        $token = Str::lower(md5("pihole"));
        //$response = Http::withHeaders([])->post('https://' . SettingManager::get("ipAddress", "pihole")->value . '/admin/api.php');
        
        $metrics = [
            "domains_being_blocked",
            "ads_percentage_today",
            "ads_blocked_today",
            "dns_queries_today",
            "unique_clients",
        ];
        
        $metricsIcons = [
            "fas fa-list",
            "fas fa-chart-pie",
            "fas fa-hand-paper",
            "fas fa-globe",
            "fas fa-network-wired",
        ];
        
        $defaultRoom = Rooms::where('default', true)->first()->id;
        
        if ($response->ok() && $response->json()) {
            $jsonResponse = $response->json();
            
            $device = Devices::where('token', $token)->First();
            if ($device !== false) {
                $device->setHeartbeat();
                
                if (!$device->approved)
                return;
                
                foreach ($metrics as $metric_key => $metric) {
                    
                    if (!isset($jsonResponse[$metric])){
                        continue;
                    }
                    
                    $property = Properties::where('type', $metric)->First();
                    if ($property == false) {
                        $property = new Properties();
                        $property->device_id = $device->id;
                        $property->room_id = $defaultRoom;
                        $property->nick_name = "pihole" . ":" . $metric;
                        $property->icon = $metricsIcons[$metric_key];
                        $property->type = $metric;
                        $property->save();
                    }
                    
                    $record = new Records();
                    $record->property_id = $property->id;
                    $record->value = (int) $jsonResponse[$metric];
                    $record->done = true;
                    $record->save();
                }
                
            } else {
                $device = new Devices();
                $device->token = $token;
                $device->hostname = "pihole";
                $device->integration = "pihole";
                $device->type = "custome";
                $device->approved = 0;
                $device->sleep = 300000;
                $device->save();
            }
        }
    }
}
