<?php

namespace App\Console\Commands;

use App\Jobs\SummarizeStoryJob;
use App\Models\LocationStory;
use Illuminate\Console\Command;

class StoriesSummarize extends Command
{
    protected $signature = 'stories:summarize {--id= : Summarize just one story id}';
    protected $description = 'Summarize pending location stories via the Claude API (or stub).';

    public function handle(): int
    {
        $query = LocationStory::where('status', 'pending');
        if ($this->option('id')) {
            $query = LocationStory::where('id', (int) $this->option('id'));
        }

        $stories = $query->get();
        if ($stories->isEmpty()) {
            $this->info('No pending stories to summarize.');
            return self::SUCCESS;
        }

        $this->info("Summarizing {$stories->count()} story/stories...");

        foreach ($stories as $s) {
            (new SummarizeStoryJob($s->id))->handle();
            $s->refresh();
            $flag = $s->status === 'ready' ? '<fg=green>ready</>' : '<fg=red>'.$s->status.'</>';
            $this->line("  #{$s->id}  [{$flag}]  {$s->title}");
        }

        return self::SUCCESS;
    }
}