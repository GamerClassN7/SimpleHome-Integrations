<?php

namespace Modules\OpenWeatherMap\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Modules\OpenWeatherMap\Jobs\Fetch;

class fetchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'simplehome:fetch:openweathermap';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch Data from Open Weather Map API';

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
        Fetch::dispatch();
        return 0;
    }
}
