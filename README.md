## About Echo

echo is born to help Post-Rock Nation reach its dreams.

## Adding a new concert provider (example: Eventim)

When a new partnership is available, add the provider by extending the existing provider registry and action pipeline.

1. Create or update a provider class in `app/Services/ConcertProviders/` that implements `ConcertProviderInterface`.
2. Return a `ProviderResult` with normalized performances (each performance must include exactly one offer).
3. Register the provider in `app/Services/ConcertProviders/ConcertProviderRegistry.php`.
4. Add any API configuration in `config/services.php` (never use `env()` outside config).
5. Ensure provider mappings exist in `ticket_provider_mappings` with the provider key.
6. Add/adjust tests using `Http::fake()` to cover success and failure logging.

For Eventim, update `app/Services/ConcertProviders/EventimConcertProvider.php` to call the Eventim API and return normalized performances, then add Eventim-specific config keys (e.g. base URL, API key) in `config/services.php`. The registry is already wired to include Eventim.

## Concert performance identifiers

Concerts are deduplicated during consolidation by hashing the artist PRN id with the performance date and location details. The identifier is generated in `app/Actions/Concerts/ConsolidateConcertPerformancesAction.php` using:

- `prnArtistId`
- `start_date`
- `country_code`
- `city` (normalized)
- `address_name` (normalized)
