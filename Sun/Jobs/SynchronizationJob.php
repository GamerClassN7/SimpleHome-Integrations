<?php

namespace Modules\Sun\Jobs;

use App\Models\Devices;
use App\Models\Locations;
use App\Models\Properties;
use App\Models\Records;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class SynchronizationJob implements ShouldQueue
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
        $device->setHeartbeat();

        if (null == $device) {
            return;
        }

        if (!$device->approved) {
            return false;
        }

        $property = Properties::where('type', 'event')->where('device_id', $device->id)->First();
        if (null == $property) {
            return;
        }

        $homeLocationCoordinates = Locations::where("name", "home")->get('position')->first();
        if (null == $homeLocationCoordinates) {
            return;
        }

        $lattitude = $homeLocationCoordinates->position[0];
        $longtitude = $homeLocationCoordinates->position[1];
        $sunInfo = date_sun_info(time(), $lattitude, $longtitude);

        if (
            time() > $sunInfo['civil_twilight_begin'] && time() < $sunInfo['sunrise']
        ) {
            $sunStage = "sunrise";
        } else if (
            time() > $sunInfo['sunrise'] && time() < $sunInfo['sunset']
        ) {
            $sunStage = "day";
        } else if (
            time() > $sunInfo['sunset'] && time() < $sunInfo['civil_twilight_end']
        ) {
            $sunStage = "sunset";
        } else if (
            time() > $sunInfo['civil_twilight_end'] && time()
        ) {
            $sunStage = "night";
        }

        if (null == $property->latestRecord || $property->latestRecord->value != $sunStage) {
            $this->createRecord($property->id, $sunStage);
        }
    }

    public function createRecord($propertyId, $value)
    {
        //TODO: States to ENUM
        $record = new Records();
        $record->property_id = $propertyId;
        $record->value = (string) $value;
        $record->origin = 'module:sun';
        $record->done = true;
        $record->save();
    }
}
