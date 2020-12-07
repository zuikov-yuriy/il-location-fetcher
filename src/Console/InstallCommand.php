<?php

namespace TheP6\ILLocationFetcher\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'il_locations:install';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install base config for il-location-fetcher';

    public function handle()
    {
        $this->comment('Publishing IL Locations Fetcher config...');
        $this->callSilent('vendor:publish', ['--tag' => 'il-location-fetcher-resource']);
    }
}
