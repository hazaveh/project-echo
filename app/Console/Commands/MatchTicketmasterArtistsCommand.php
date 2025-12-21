<?php

namespace App\Console\Commands;

use App\Actions\Sources\Ticketmaster\MatchAttractionForArtistAction;
use App\Models\Artist;
use Illuminate\Console\Command;

class MatchTicketmasterArtistsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ticketmaster:match-artists {--sleep=100000} {--cooldown-days=1} {--refresh-days=30}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Match artists with Ticketmaster attractions and store mappings';

    /**
     * Execute the console command.
     */
    public function handle(MatchAttractionForArtistAction $action): int
    {
        if (! config('services.ticketmaster.key')) {
            $this->error('Missing Ticketmaster API key. Set TICKETMASTER_KEY in your environment.');

            return self::FAILURE;
        }

        $provider = 'ticketmaster';
        $sleepMicroseconds = max(0, (int) $this->option('sleep'));
        $cooldownDays = max(0, (int) $this->option('cooldown-days'));
        $refreshDays = max(0, (int) $this->option('refresh-days'));
        $now = now();
        $refreshCutoff = $now->copy()->subDays($refreshDays);
        $processed = 0;
        $matched = 0;
        $missed = 0;

        $query = Artist::query()
            ->where(function ($query) use ($provider, $refreshCutoff) {
                $query->whereDoesntHave('ticketProviderMappings', function ($query) use ($provider) {
                    $query->where('provider', $provider);
                })->orWhereHas('ticketProviderMappings', function ($query) use ($provider, $refreshCutoff) {
                    $query->where('provider', $provider)
                        ->where(function ($query) use ($refreshCutoff) {
                            $query->whereNull('last_synced_at')
                                ->orWhere('last_synced_at', '<=', $refreshCutoff);
                        });
                });
            });

        if ($cooldownDays > 0) {
            $cooldownCutoff = $now->copy()->subDays($cooldownDays);

            $query->where(function ($query) use ($cooldownCutoff) {
                $query->whereNull('ticketmaster_match_attempted_at')
                    ->orWhere('ticketmaster_match_attempted_at', '<', $cooldownCutoff);
            });
        }

        $query->orderBy('id');

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('No artists eligible for Ticketmaster matching.');

            return self::SUCCESS;
        }

        $this->info("Matching {$total} artists against Ticketmaster...");

        $progress = $this->output->createProgressBar($total);
        $progress->start();

        $command = $this;

        $query->chunkById(100, function ($artists) use ($action, $sleepMicroseconds, &$processed, &$matched, &$missed, $progress, $command) {
            foreach ($artists as $artist) {
                $result = $action->execute($artist);

                if ($result->ok) {
                    $artist->forceFill([
                        'ticketmaster_match_attempted_at' => now(),
                    ])->save();
                }

                $processed++;
                $result->mapping ? $matched++ : $missed++;

                if ($command->output->isVerbose()) {
                    $status = $result->mapping ? 'matched' : 'missed';
                    $command->line(" [{$status}] {$artist->id} {$artist->name}");
                }

                $progress->advance();

                if ($sleepMicroseconds > 0) {
                    usleep($sleepMicroseconds);
                }
            }
        });

        $progress->finish();
        $this->newLine(2);
        $this->info("Processed {$processed} artists. Matched: {$matched}. Missed: {$missed}.");

        return self::SUCCESS;
    }
}
