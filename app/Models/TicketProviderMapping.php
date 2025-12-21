<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketProviderMapping extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'artist_id',
        'provider',
        'provider_artist_id',
        'status',
        'confidence',
        'resolution_method',
        'last_synced_at',
    ];

    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }

    protected function casts(): array
    {
        return [
            'confidence' => 'float',
            'last_synced_at' => 'datetime',
        ];
    }
}
