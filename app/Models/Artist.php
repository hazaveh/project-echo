<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Artist extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $guarded = ['id'];

    public function ticketProviderMappings(): HasMany
    {
        return $this->hasMany(TicketProviderMapping::class);
    }

    public function ticketProvider(string $provider): ?TicketProviderMapping
    {
        return $this->ticketProviderMappings()
            ->where('provider', $provider)
            ->first();
    }

    public function scopeByPrnArtistId(Builder $query, int $prnArtistId): Builder
    {
        return $query->where('prn_artist_id', $prnArtistId);
    }

    public function scopeBySpotify(Builder $query, string $spotify): Builder
    {
        return $query->where('spotify', $spotify);
    }

    public static function findByPrnArtistId(int $prnArtistId): ?self
    {
        return self::query()->byPrnArtistId($prnArtistId)->first();
    }

    public static function findBySpotify(string $spotify): ?self
    {
        return self::query()->bySpotify($spotify)->first();
    }
}
