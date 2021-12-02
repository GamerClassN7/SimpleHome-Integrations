<?php

namespace Modules\ShellyCloud\Console;

use Illuminate\Console\Command;
use Modules\ShellyCloud\Jobs\Fetch;
use Modules\ShellyCloud\Jobs\Sync;


class SyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'simplehome:shellycloud:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retrieve all devices from ShellyCloud API';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Sync::dispatchNow();
        $this->info('Synchronization done!');
        Fetch::dispatchNow();
        $this->info('Running Fetch to add get states!');

        return 0;
    }
}
