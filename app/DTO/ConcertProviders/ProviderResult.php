<?php

namespace App\DTO\ConcertProviders;

class ProviderResult
{
    /**
     * @param  array<int, array{
     *     identifier: string|null,
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
     * }>  $performances
     */
    public function __construct(
        public readonly bool $ok,
        public readonly ?int $statusCode,
        public readonly ?array $payload,
        public readonly ?string $errorMessage,
        public readonly array $performances
    ) {}
}
