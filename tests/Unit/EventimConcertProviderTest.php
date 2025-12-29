<?php

use App\Actions\Affiliates\GenerateAwinLinkAction;
use App\Services\ConcertProviders\EventimConcertProvider;
use Tests\TestCase;

uses(TestCase::class);

it('returns performances for a matching eventim artist path', function () {
    $awinAction = new class extends GenerateAwinLinkAction
    {
        public function execute(string $originalUrl, string $advertiserId): string
        {
            return 'https://www.awin1.com/track/eventim';
        }
    };

    $provider = new EventimConcertProvider($awinAction);
    $providerArtistId = '/artist/godspeed-you-black-emperor/';

    $method = new ReflectionMethod($provider, 'performancesFromEventimPayload');
    $method->setAccessible(true);
    $performances = $method->invoke($provider, eventimPayload(), $providerArtistId);

    expect($performances)->toHaveCount(5)
        ->and($performances[0]['start_date'])->toBe('2026-03-15')
        ->and($performances[0]['status'])->toBe('onsale')
        ->and($performances[0]['offers'][0]['provider'])->toBe('eventim')
        ->and($performances[0]['offers'][0]['url'])->toBe('https://www.awin1.com/track/eventim');
});

it('returns an empty performance list when the artist path does not match', function () {
    $awinAction = new class extends GenerateAwinLinkAction
    {
        public function execute(string $originalUrl, string $advertiserId): string
        {
            return 'https://www.awin1.com/track/eventim';
        }
    };

    $provider = new EventimConcertProvider($awinAction);
    $providerArtistId = '/artist/other-artist/';

    $method = new ReflectionMethod($provider, 'performancesFromEventimPayload');
    $method->setAccessible(true);
    $performances = $method->invoke($provider, eventimPayload(), $providerArtistId);

    expect($performances)->toBeEmpty();
});

/**
 * @return array<string, mixed>
 */
function eventimPayload(): array
{
    $payload = json_decode(file_get_contents(base_path('tests/Fixtures/eventim-search-response.json')), true);

    return is_array($payload) ? $payload : [];
}
