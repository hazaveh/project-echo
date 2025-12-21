<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConcertProviderSyncLog extends Model
{
    /** @use HasFactory<\Database\Factories\ConcertProviderSyncLogFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'prn_artist_id',
        'artist_id',
        'provider',
        'provider_artist_id',
        'ok',
        'status_code',
        'result_count',
        'duration_ms',
        'error_message',
        'response_payload',
    ];

    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }

    protected function casts(): array
    {
        return [
            'ok' => 'boolean',
            'status_code' => 'integer',
            'result_count' => 'integer',
            'duration_ms' => 'integer',
        ];
    }
}
