<?php

namespace App\Actions\Concerts;

use App\DTO\ConcertProviders\ProviderResult;
use App\Models\Artist;
use App\Models\ConcertProviderSyncLog;
use App\Models\TicketProviderMapping;

class LogProviderConcertSyncAction
{
    public function execute(
        Artist $artist,
        TicketProviderMapping $mapping,
        ProviderResult $result,
        ?int $durationMs = null
    ): ConcertProviderSyncLog {
        return ConcertProviderSyncLog::create([
            'prn_artist_id' => $artist->prn_artist_id,
            'artist_id' => $artist->id,
            'provider' => $mapping->provider,
            'provider_artist_id' => $mapping->provider_artist_id,
            'ok' => $result->ok,
            'status_code' => $result->statusCode,
            'result_count' => $result->ok ? count($result->performances) : null,
            'duration_ms' => $durationMs,
            'error_message' => $result->errorMessage,
            'response_payload' => $this->encodePayload($result->payload),
        ]);
    }

    private function encodePayload(?array $payload): ?string
    {
        if ($payload === null) {
            return null;
        }

        $encoded = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $encoded === false ? null : $encoded;
    }
}
