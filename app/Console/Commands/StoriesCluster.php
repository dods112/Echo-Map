<?php

namespace App\Console\Commands;

use App\Jobs\ClusterPinsJob;
use Illuminate\Console\Command;

class StoriesCluster extends Command
{
    protected $signature = 'stories:cluster
        {--from= : YYYY-MM-DD lower bound}
        {--to=   : YYYY-MM-DD upper bound}
        {--min=3 : Minimum public pins per cluster}
        {--radius=150 : Cluster cell size in meters}';

    protected $description = 'Cluster nearby PUBLIC pins into location stories and dispatch summarization.';

    public function handle(): int
    {
        $from   = $this->option('from');
        $to     = $this->option('to');
        $min    = (int) $this->option('min');
        $radius = (int) $this->option('radius');

        $this->info("Clustering public pins (min={$min}, radius={$radius}m)...");

        // Run synchronously for a simple CLI experience.
        (new ClusterPinsJob($from ?: null, $to ?: null, $min, $radius))->handle();

        $this->info('Done. Dispatched summarization for new pending stories.');
        return self::SUCCESS;
    }
}