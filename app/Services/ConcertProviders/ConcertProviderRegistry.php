<?php

namespace App\Services\ConcertProviders;

class ConcertProviderRegistry
{
    public function __construct(
        private TicketmasterConcertProvider $ticketmasterConcertProvider,
        private EventimConcertProvider $eventimConcertProvider
    ) {}

    /**
     * @return array<int, ConcertProviderInterface>
     */
    public function all(): array
    {
        return [
            $this->ticketmasterConcertProvider,
            $this->eventimConcertProvider,
        ];
    }

    public function resolve(string $providerKey): ?ConcertProviderInterface
    {
        foreach ($this->all() as $provider) {
            if ($provider->providerKey() === $providerKey) {
                return $provider;
            }
        }

        return null;
    }
}
