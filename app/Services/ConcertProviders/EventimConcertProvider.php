<?php

namespace App\Services\ConcertProviders;

use App\Actions\Affiliates\GenerateAwinLinkAction;
use App\DTO\ConcertProviders\ProviderResult;
use App\Models\Artist;
use App\Models\TicketProviderMapping;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverWait;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class EventimConcertProvider implements ConcertProviderInterface
{
    private const SEARCH_URL = 'https://public-api.eventim.com/websearch/search/api/exploration/v2/productGroups';

    private const REQUEST_TIMEOUT_SECONDS = 10;

    private const CONNECT_TIMEOUT_SECONDS = 5;

    private const WEB_ID = 'web__eventim-de';

    private const LANGUAGE = 'de';

    private const PAGE = 1;

    private const SORT = 'DateAsc';

    private const TOP = 50;

    private const RETAIL_PARTNER = 'EVE';

    private const BASE_URL = 'https://www.eventim.de/';

    private const ADVERTISER_ID = '11388';

    public function __construct(
        private GenerateAwinLinkAction $generateAwinLinkAction
    ) {}

    public function providerKey(): string
    {
        return 'eventim';
    }

    public function providerName(): string
    {
        return 'Eventim';
    }

    public function supports(TicketProviderMapping $mapping): bool
    {
        return $mapping->provider === $this->providerKey();
    }

    public function fetchConcerts(Artist $artist, TicketProviderMapping $mapping): ProviderResult
    {
        $searchTerm = $this->searchTerm($artist, $mapping);

        if ($searchTerm === null) {
            return new ProviderResult(
                ok: false,
                statusCode: null,
                payload: null,
                errorMessage: 'Eventim search term unavailable.',
                performances: []
            );
        }

        $webDriverUrls = $this->webDriverUrls();

        if ($webDriverUrls === []) {
            return new ProviderResult(
                ok: false,
                statusCode: null,
                payload: null,
                errorMessage: 'Eventim WebDriver URL not configured.',
                performances: []
            );
        }

        $lastResult = null;

        foreach ($webDriverUrls as $webDriverUrl) {
            $driver = $this->createWebDriver($webDriverUrl, $artist, $mapping);

            if ($driver === null) {
                continue;
            }

            $result = $this->fetchConcertsWithWebDriver($driver, $artist, $mapping, $searchTerm);
            $lastResult = $result;

            if ($result->ok) {
                return $result;
            }
        }

        return $lastResult ?? new ProviderResult(
            ok: false,
            statusCode: null,
            payload: null,
            errorMessage: 'Eventim WebDriver session failed.',
            performances: []
        );
    }

    /**
     * @return array<int, string>
     */
    private function webDriverUrls(): array
    {
        $url = config('services.webdriver.url');

        if (! is_string($url) || $url === '') {
            return [];
        }

        $normalized = rtrim($url, '/');
        $urls = [$normalized];

        if (str_ends_with($normalized, '/wd/hub')) {
            $urls[] = rtrim(substr($normalized, 0, -7), '/');
        } else {
            $urls[] = $normalized.'/wd/hub';
        }

        return array_values(array_unique(array_filter($urls)));
    }

    private function createWebDriver(string $webDriverUrl, Artist $artist, TicketProviderMapping $mapping): ?RemoteWebDriver
    {
        try {
            $options = new ChromeOptions;
            $options->addArguments([
                '--headless=new',
                '--disable-gpu',
                '--no-sandbox',
                '--disable-dev-shm-usage',
                '--window-size=1280,800',
            ]);

            $capabilities = DesiredCapabilities::chrome();
            $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);

            return RemoteWebDriver::create(
                $webDriverUrl,
                $capabilities,
                self::REQUEST_TIMEOUT_SECONDS * 1000,
                self::CONNECT_TIMEOUT_SECONDS * 1000
            );
        } catch (Throwable $exception) {
            report($exception);
            Log::warning('Eventim WebDriver session creation failed.', [
                'prn_artist_id' => $artist->prn_artist_id,
                'artist_id' => $artist->id,
                'provider_artist_id' => $mapping->provider_artist_id,
                'web_driver_url' => $webDriverUrl,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function fetchConcertsWithWebDriver(
        RemoteWebDriver $driver,
        Artist $artist,
        TicketProviderMapping $mapping,
        string $searchTerm
    ): ProviderResult {
        try {
            $driver->manage()->timeouts()->pageLoadTimeout(self::REQUEST_TIMEOUT_SECONDS);
            $driver->get(self::BASE_URL);
            $this->waitForCookieSeed($driver);

            $url = self::SEARCH_URL.'?'.http_build_query($this->eventimQueryParams($searchTerm));
            $driver->get($url);

            $source = $this->waitForPageSource($driver);
            $payload = $this->decodePayloadFromPageSource($source);

            if (! is_array($payload)) {
                $errorMessage = str_contains($source, 'Access Denied')
                    ? 'Eventim WebDriver request blocked.'
                    : 'Eventim WebDriver response could not be parsed.';

                return new ProviderResult(
                    ok: false,
                    statusCode: null,
                    payload: null,
                    errorMessage: $errorMessage,
                    performances: []
                );
            }

            $performances = $this->performancesFromEventimPayload($payload, $mapping->provider_artist_id);

            return new ProviderResult(
                ok: true,
                statusCode: null,
                payload: $payload,
                errorMessage: null,
                performances: $performances
            );
        } catch (Throwable $exception) {
            report($exception);
            Log::error('Eventim WebDriver request threw an exception.', [
                'prn_artist_id' => $artist->prn_artist_id,
                'artist_id' => $artist->id,
                'provider_artist_id' => $mapping->provider_artist_id,
                'message' => $exception->getMessage(),
            ]);

            return new ProviderResult(
                ok: false,
                statusCode: null,
                payload: null,
                errorMessage: $exception->getMessage(),
                performances: []
            );
        } finally {
            $driver->quit();
        }
    }

    private function waitForCookieSeed(RemoteWebDriver $driver): void
    {
        $wait = new WebDriverWait($driver, self::REQUEST_TIMEOUT_SECONDS);

        try {
            $wait->until(function () use ($driver) {
                $cookies = $driver->manage()->getCookies();

                return $cookies !== [];
            });
        } catch (Throwable $exception) {
            report($exception);
            Log::warning('Eventim cookies were not seeded before timeout.');
        }
    }

    private function waitForPageSource(RemoteWebDriver $driver): string
    {
        $wait = new WebDriverWait($driver, self::REQUEST_TIMEOUT_SECONDS);

        $wait->until(function () use ($driver) {
            $source = $driver->getPageSource();

            return str_contains($source, '{') || str_contains($source, '[');
        });

        return $driver->getPageSource();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodePayloadFromPageSource(string $source): ?array
    {
        $payload = $this->decodeJson(trim($source));

        if (is_array($payload)) {
            return $payload;
        }

        if (preg_match('/<pre[^>]*>(.*?)<\\/pre>/si', $source, $matches) === 1) {
            $json = html_entity_decode($matches[1]);
            $payload = $this->decodeJson(trim($json));

            if (is_array($payload)) {
                return $payload;
            }
        }

        $body = strip_tags($source);
        $payload = $this->decodeJson(trim(html_entity_decode($body)));

        return is_array($payload) ? $payload : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJson(string $payload): ?array
    {
        if ($payload === '') {
            return null;
        }

        $decoded = json_decode($payload, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function eventimQueryParams(string $searchTerm): array
    {
        return [
            'search_term' => $searchTerm,
            'webId' => self::WEB_ID,
            'language' => self::LANGUAGE,
            'page' => self::PAGE,
            'sort' => self::SORT,
            'top' => self::TOP,
            'retail_partner' => self::RETAIL_PARTNER,
        ];
    }

    private function searchTerm(Artist $artist, TicketProviderMapping $mapping): ?string
    {
        if (is_string($artist->name) && $artist->name !== '') {
            return $artist->name;
        }

        if (is_string($mapping->provider_artist_id) && $mapping->provider_artist_id !== '') {
            return $mapping->provider_artist_id;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array{
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
     * }>
     */
    private function performancesFromEventimPayload(array $payload, ?string $providerArtistId): array
    {
        $productGroups = data_get($payload, 'productGroups', []);

        if (! is_array($productGroups)) {
            return [];
        }

        $normalizedPath = $this->normalizeArtistPath($providerArtistId);

        if ($normalizedPath === '') {
            return [];
        }

        $productGroup = $this->matchProductGroup($productGroups, $normalizedPath);

        if ($productGroup === null) {
            return [];
        }

        return $this->performancesFromEventimProductGroup($productGroup);
    }

    /**
     * @param  array<int, mixed>  $productGroups
     * @return array<string, mixed>|null
     */
    private function matchProductGroup(array $productGroups, string $normalizedPath): ?array
    {
        foreach ($productGroups as $productGroup) {
            if (! is_array($productGroup)) {
                continue;
            }

            $path = $this->normalizeArtistPath(data_get($productGroup, 'url.path'));

            if ($path === $normalizedPath) {
                return $productGroup;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $productGroup
     * @return array<int, array{
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
     * }>
     */
    private function performancesFromEventimProductGroup(array $productGroup): array
    {
        $products = data_get($productGroup, 'products', []);

        if (! is_array($products)) {
            return [];
        }

        $performances = [];

        foreach ($products as $product) {
            if (! is_array($product)) {
                continue;
            }

            $performance = $this->performanceFromEventimProduct($product);

            if ($performance !== null) {
                $performances[] = $performance;
            }
        }

        return $performances;
    }

    /**
     * @param  array<string, mixed>  $product
     * @return array{
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
     * }|null
     */
    private function performanceFromEventimProduct(array $product): ?array
    {
        $liveEntertainment = data_get($product, 'typeAttributes.liveEntertainment', []);

        if (! is_array($liveEntertainment)) {
            return null;
        }

        $location = data_get($liveEntertainment, 'location', []);
        $location = is_array($location) ? $location : [];

        $status = $this->normalizeStatus($this->stringOrNull(data_get($product, 'status')));
        $eventimUrl = $this->productUrl($product);
        $country = $this->countryFromDomain($eventimUrl);

        return [
            'identifier' => null,
            'name' => $this->stringOrNull(data_get($product, 'name')),
            'type' => 'concert',
            'start_date' => $this->dateOrNull(data_get($liveEntertainment, 'startDate')),
            'end_date' => null,
            'status' => $status,
            'address_name' => $this->stringOrNull(data_get($location, 'name')),
            'address' => null,
            'city' => $this->stringOrNull(data_get($location, 'city')),
            'country' => $country['name'],
            'country_code' => $country['code'],
            'postal_code' => $this->stringOrNull(data_get($location, 'postalCode')),
            'latitude' => $this->coordinateOrNull(data_get($location, 'geoLocation.latitude')),
            'longitude' => $this->coordinateOrNull(data_get($location, 'geoLocation.longitude')),
            'offers' => [
                [
                    'provider' => $this->providerKey(),
                    'provider_name' => $this->providerName(),
                    'url' => $this->offerUrl($eventimUrl),
                ],
            ],
        ];
    }

    private function normalizeArtistPath(?string $value): string
    {
        if (! is_string($value) || $value === '') {
            return '';
        }

        $path = $value;

        if (str_contains($path, '://')) {
            $parsed = parse_url($path, PHP_URL_PATH);

            if (is_string($parsed) && $parsed !== '') {
                $path = $parsed;
            }
        }

        $path = strtolower(trim($path));
        $path = rtrim($path, '/');

        if (! str_starts_with($path, '/')) {
            $path = '/'.$path;
        }

        if (! str_starts_with($path, '/artist/')) {
            $path = '/artist/'.ltrim($path, '/');
        }

        return $path;
    }

    private function normalizeStatus(?string $status): string
    {
        if (! is_string($status) || $status === '') {
            return 'scheduled';
        }

        $normalized = strtolower($status);

        return match ($normalized) {
            'available' => 'onsale',
            'soldout', 'sold_out' => 'soldout',
            'cancelled', 'canceled' => 'cancelled',
            'postponed' => 'postponed',
            default => $normalized,
        };
    }

    private function dateOrNull(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }

    private function coordinateOrNull(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    /**
     * @param  array<string, mixed>  $product
     */
    private function productUrl(array $product): ?string
    {
        $link = $this->stringOrNull(data_get($product, 'link'));

        if ($link !== null) {
            return $link;
        }

        $domain = $this->stringOrNull(data_get($product, 'url.domain'));
        $path = $this->stringOrNull(data_get($product, 'url.path'));

        if ($domain === null || $path === null) {
            return null;
        }

        return rtrim($domain, '/').'/'.ltrim($path, '/');
    }

    /**
     * @return array{code: string|null, name: string|null}
     */
    private function countryFromDomain(?string $url): array
    {
        $host = $url !== null ? parse_url($url, PHP_URL_HOST) : null;

        if (! is_string($host) || $host === '') {
            $host = parse_url(self::BASE_URL, PHP_URL_HOST);
        }

        if (! is_string($host) || $host === '') {
            return ['code' => null, 'name' => null];
        }

        $normalizedHost = strtolower($host);

        if (str_ends_with($normalizedHost, '.de')) {
            return ['code' => 'DE', 'name' => 'Germany'];
        }

        return ['code' => null, 'name' => null];
    }

    private function offerUrl(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        return $this->generateAwinLinkAction->execute($url, self::ADVERTISER_ID);
    }
}
