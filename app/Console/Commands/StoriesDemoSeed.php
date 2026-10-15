<?php

namespace App\Console\Commands;

use App\Models\Pin;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class StoriesDemoSeed extends Command
{
    protected $signature = 'stories:demo-seed
        {--lat=14.5533 : Center latitude}
        {--lng=121.0450 : Center longitude}
        {--user=1     : Owner user id}
        {--wipe        : Delete ALL pins first (only use on a dev DB)}';

    protected $description = 'Create demo public/private/friends pins near a point, to test clustering privacy.';

    public function handle(): int
    {
        $lat = (float) $this->option('lat');
        $lng = (float) $this->option('lng');

        $userId = (int) $this->option('user');
        if (! User::find($userId)) {
            $this->error("User #{$userId} not found. Pass --user=<id> of an existing account.");
            return self::FAILURE;
        }

        if ($this->option('wipe')) {
            if (! $this->confirm('This will DELETE all pins. Are you sure?', false)) {
                return self::SUCCESS;
            }
            Pin::query()->delete();
        }

        // Public pins — will cluster into a story.
        $public = [
            ['Campus fountain',  'Where everyone meets between classes.'],
            ['Library steps',    'Studying spot during finals week.'],
            ['Old acacia tree',  'Best shade on hot afternoons.'],
            ['Coffee cart',      'Cheap iced coffee, always a line.'],
            ['Bench by the gym', 'Where the running club stretches.'],
        ];

        // Private + friends — must NEVER appear in a story.
        $private = [
            ['Dorm room',    'Personal notes, not for anyone else.'],
            ['Diary spot',   'Nothing to share here.'],
        ];
        $friends = [
            ['Secret study room', 'Only my study group knows.'],
            ['Hidden bench',      'Friends-only hangout.'],
        ];

        $jitter = fn ($i) => (($i % 5) - 2) * 0.0002; // tiny offsets so pins cluster but aren't identical

        foreach ($public as $i => [$title, $body]) {
            Pin::create([
                'user_id'     => $userId,
                'title'       => $title,
                'body'        => $body,
                'latitude'    => $lat + $jitter($i),
                'longitude'   => $lng + $jitter($i + 1),
                'theme'       => ['Memory', 'History', 'Event', 'Hangout'][$i % 4],
                'visibility'  => 'public',
                'event_date'  => Carbon::now()->subDays($i),
                'is_hidden'   => false,
            ]);
        }

        foreach ($private as $i => [$title, $body]) {
            Pin::create([
                'user_id'     => $userId,
                'title'       => $title,
                'body'        => $body,
                'latitude'    => $lat + $jitter($i + 2),
                'longitude'   => $lng + $jitter($i + 2),
                'theme'       => 'Other',
                'visibility'  => 'private',
                'is_hidden'   => false,
            ]);
        }

        foreach ($friends as $i => [$title, $body]) {
            Pin::create([
                'user_id'     => $userId,
                'title'       => $title,
                'body'        => $body,
                'latitude'    => $lat + $jitter($i + 3),
                'longitude'   => $lng + $jitter($i + 3),
                'theme'       => 'Other',
                'visibility'  => 'friends',
                'is_hidden'   => false,
            ]);
        }

        $this->info('Seeded 5 public + 2 private + 2 friends pins near ' . $lat . ', ' . $lng);
        $this->line('Now run:  php artisan stories:cluster  &&  php artisan stories:summarize');
        return self::SUCCESS;
    }
}