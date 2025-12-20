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
    protected $signature = 'ticketmaster:match-artists {--sleep=100000}';

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

        $sleepMicroseconds = max(0, (int) $this->option('sleep'));
        $processed = 0;
        $matched = 0;
        $missed = 0;

        $query = Artist::query()
            ->whereDoesntHave('ticketProviderMappings', function ($query) {
                $query->where('provider', 'ticketmaster');
            })
            ->orderBy('id');

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('No artists found without Ticketmaster mappings.');

            return self::SUCCESS;
        }

        $this->info("Matching {$total} artists against Ticketmaster...");

        $progress = $this->output->createProgressBar($total);
        $progress->start();

        $command = $this;

        $query->chunkById(100, function ($artists) use ($action, $sleepMicroseconds, &$processed, &$matched, &$missed, $progress, $command) {
            foreach ($artists as $artist) {
                $mapping = $action->execute($artist);

                $processed++;
                $mapping ? $matched++ : $missed++;

                if ($command->output->isVerbose()) {
                    $status = $mapping ? 'matched' : 'missed';
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
