<?php

namespace App\Actions\Concerts;

use App\DTO\ConcertProviders\ProviderResult;

class ConsolidateConcertPerformancesAction
{
    /**
     * @param  array<int, ProviderResult>  $providerResults
     * @return array<int, array{
     *     identifier: string,
     *     name: string|null,
     *     type: string,
     *     start_date: string|null,
     *     end_date: string|null,
     *     status: string,
     *     address_name: string|null,
     *     address: string|null,
     *     city: string|null,
     *     country: string|null,
     *     country_code: string|null,
     *     postal_code: string|null,
     *     latitude: float|null,
     *     longitude: float|null,
     *     offers: array<int, array{
     *         provider: string,
     *         provider_name: string,
     *         url: string|null
     *     }>
     * }>
     */
    public function execute(int $prnArtistId, array $providerResults): array
    {
        $consolidated = [];

        foreach ($providerResults as $result) {
            foreach ($result->performances as $performance) {
                if (! is_array($performance)) {
                    continue;
                }

                $identifier = $this->performanceIdentifier($prnArtistId, $performance);
                $performance['identifier'] = $identifier;

                if (! array_key_exists($identifier, $consolidated)) {
                    $consolidated[$identifier] = $performance;

                    continue;
                }

                $consolidated[$identifier] = $this->mergePerformances($consolidated[$identifier], $performance);
            }
        }

        $performances = array_values($consolidated);

        usort($performances, function (array $left, array $right) {
            $leftDate = $left['start_date'] ?? null;
            $rightDate = $right['start_date'] ?? null;

            if ($leftDate === $rightDate) {
                return 0;
            }

            if ($leftDate === null) {
                return 1;
            }

            if ($rightDate === null) {
                return -1;
            }

            return $leftDate <=> $rightDate;
        });

        return $performances;
    }

    /**
     * @param  array{
     *     identifier?: string|null,
     *     name?: string|null,
     *     type?: string,
     *     start_date?: string|null,
     *     end_date?: string|null,
     *     status?: string,
     *     address_name?: string|null,
     *     address?: string|null,
     *     city?: string|null,
     *     country?: string|null,
     *     country_code?: string|null,
     *     postal_code?: string|null,
     *     latitude?: float|null,
     *     longitude?: float|null,
     *     offers?: array<int, array{
     *         provider: string,
     *         provider_name: string,
     *         url: string|null
     *     }>
     * }  $performance
     */
    private function performanceIdentifier(int $prnArtistId, array $performance): string
    {
        $startDate = (string) ($performance['start_date'] ?? '');
        $countryCode = (string) ($performance['country_code'] ?? '');
        $normalizedCity = $this->normalizeValue($performance['city'] ?? null);
        $normalizedVenue = $this->normalizeValue($performance['address_name'] ?? null);

        return 'echo:perf:'.sha1($prnArtistId.$startDate.$countryCode.$normalizedCity.$normalizedVenue);
    }

    /**
     * @param  array{
     *     identifier: string,
     *     name: string|null,
     *     type: string,
     *     start_date: string|null,
     *     end_date: string|null,
     *     status: string,
     *     address_name: string|null,
     *     address: string|null,
     *     city: string|null,
     *     country: string|null,
     *     country_code: string|null,
     *     postal_code: string|null,
     *     latitude: float|null,
     *     longitude: float|null,
     *     offers: array<int, array{
     *         provider: string,
     *         provider_name: string,
     *         url: string|null
     *     }>
     * }  $existing
     * @param  array{
     *     identifier: string,
     *     name: string|null,
     *     type: string,
     *     start_date: string|null,
     *     end_date: string|null,
     *     status: string,
     *     address_name: string|null,
     *     address: string|null,
     *     city: string|null,
     *     country: string|null,
     *     country_code: string|null,
     *     postal_code: string|null,
     *     latitude: float|null,
     *     longitude: float|null,
     *     offers: array<int, array{
     *         provider: string,
     *         provider_name: string,
     *         url: string|null
     *     }>
     * }  $incoming
     * @return array{
     *     identifier: string,
     *     name: string|null,
     *     type: string,
     *     start_date: string|null,
     *     end_date: string|null,
     *     status: string,
     *     address_name: string|null,
     *     address: string|null,
     *     city: string|null,
     *     country: string|null,
     *     country_code: string|null,
     *     postal_code: string|null,
     *     latitude: float|null,
     *     longitude: float|null,
     *     offers: array<int, array{
     *         provider: string,
     *         provider_name: string,
     *         url: string|null
     *     }>
     * }
     */
    private function mergePerformances(array $existing, array $incoming): array
    {
        $merged = $existing;
        $fields = [
            'name',
            'type',
            'start_date',
            'end_date',
            'address_name',
            'address',
            'city',
            'country',
            'country_code',
            'postal_code',
            'latitude',
            'longitude',
        ];

        foreach ($fields as $field) {
            $merged[$field] = $this->preferNonNull($merged[$field] ?? null, $incoming[$field] ?? null);
        }

        $merged['status'] = $this->preferredStatus($merged['status'] ?? null, $incoming['status'] ?? null);
        $merged['offers'] = $this->mergeOffers($merged['offers'] ?? [], $incoming['offers'] ?? []);

        return $merged;
    }

    private function preferNonNull(mixed $existing, mixed $incoming): mixed
    {
        if ($this->isNullish($existing) && ! $this->isNullish($incoming)) {
            return $incoming;
        }

        return $existing;
    }

    private function isNullish(mixed $value): bool
    {
        return $value === null || $value === '';
    }

    private function normalizeValue(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        $normalized = strtolower(trim($value));
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return $normalized ?? '';
    }

    /**
     * @param  array<int, array{
     *     provider: string,
     *     provider_name: string,
     *     url: string|null
     * }>  $existing
     * @param  array<int, array{
     *     provider: string,
     *     provider_name: string,
     *     url: string|null
     * }>  $incoming
     * @return array<int, array{
     *     provider: string,
     *     provider_name: string,
     *     url: string|null
     * }>
     */
    private function mergeOffers(array $existing, array $incoming): array
    {
        $offers = [];

        foreach (array_merge($existing, $incoming) as $offer) {
            if (! is_array($offer)) {
                continue;
            }

            $provider = $offer['provider'] ?? null;

            if (! is_string($provider) || $provider === '') {
                continue;
            }

            if (! array_key_exists($provider, $offers)) {
                $offers[$provider] = $offer;
            }
        }

        return array_values($offers);
    }

    private function preferredStatus(?string $left, ?string $right): string
    {
        $leftValue = $left ?? 'scheduled';
        $rightValue = $right ?? 'scheduled';

        return $this->statusPriority($leftValue) <= $this->statusPriority($rightValue)
            ? $leftValue
            : $rightValue;
    }

    private function statusPriority(string $status): int
    {
        $normalized = strtolower($status);

        return match ($normalized) {
            'cancelled', 'canceled', 'postponed' => 1,
            'soldout', 'sold_out' => 2,
            'onsale', 'on_sale' => 3,
            default => 4,
        };
    }
}
