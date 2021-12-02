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
        $this->info('API will be fetched 3 times due to limitation of laravel running task each minute only...');
        Fetch::dispatchNow();
        $this->info('First fetch done!');
        sleep(15);

        Fetch::dispatchNow();
        $this->info('Second fetch done!');
        sleep(15);

        Fetch::dispatchNow();
        $this->info('Thirth fetch done!');
        return 0;
    }
}
