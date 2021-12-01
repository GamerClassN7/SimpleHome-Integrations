<?php

namespace Modules\ShellyCloud\Console;

use Illuminate\Console\Command;
use Modules\ShellyCloud\Jobs\Fetch;

class FetchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'simplehome:shellycloud:fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retrieve states of all devices from ShellyCloud API';

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
        Fetch::dispatchNow();
        sleep(3);
        Fetch::dispatchNow();

        return 0;
    }
}
