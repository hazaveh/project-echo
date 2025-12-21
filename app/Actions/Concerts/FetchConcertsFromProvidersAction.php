<?php

namespace App\Actions\Concerts;

use App\DTO\ConcertProviders\ProviderResult;
use App\Models\Artist;
use App\Models\TicketProviderMapping;
use App\Services\ConcertProviders\ConcertProviderRegistry;
use Illuminate\Support\Collection;
use Throwable;

class FetchConcertsFromProvidersAction
{
    public function __construct(
        private ConcertProviderRegistry $concertProviderRegistry,
        private LogProviderConcertSyncAction $logProviderConcertSyncAction
    ) {}

    /**
     * @param  Collection<int, TicketProviderMapping>  $mappings
     * @return array<int, ProviderResult>
     */
    public function execute(Artist $artist, Collection $mappings): array
    {
        $results = [];

        foreach ($mappings as $mapping) {
            $startedAt = microtime(true);
            $provider = $this->concertProviderRegistry->resolve($mapping->provider);

            if ($provider === null) {
                $result = new ProviderResult(
                    ok: false,
                    statusCode: null,
                    payload: null,
                    errorMessage: 'Provider not registered.',
                    performances: []
                );

                $this->logProviderConcertSyncAction->execute($artist, $mapping, $result, $this->durationMs($startedAt));
                $results[] = $result;

                continue;
            }

            if (! $provider->supports($mapping)) {
                $result = new ProviderResult(
                    ok: false,
                    statusCode: null,
                    payload: null,
                    errorMessage: 'Provider not supported.',
                    performances: []
                );

                $this->logProviderConcertSyncAction->execute($artist, $mapping, $result, $this->durationMs($startedAt));
                $results[] = $result;

                continue;
            }

            try {
                $result = $provider->fetchConcerts($artist, $mapping);
            } catch (Throwable $exception) {
                $result = new ProviderResult(
                    ok: false,
                    statusCode: null,
                    payload: null,
                    errorMessage: $exception->getMessage(),
                    performances: []
                );
            }

            $result = $this->normalizeResult($result);
            $this->logProviderConcertSyncAction->execute($artist, $mapping, $result, $this->durationMs($startedAt));
            $results[] = $result;
        }

        return $results;
    }

    private function durationMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }

    private function normalizeResult(ProviderResult $result): ProviderResult
    {
        if ($result->ok && $result->statusCode !== null && $result->statusCode >= 400) {
            return new ProviderResult(
                ok: false,
                statusCode: $result->statusCode,
                payload: $result->payload,
                errorMessage: $result->errorMessage ?? 'Provider request failed.',
                performances: []
            );
        }

        return $result;
    }
}
