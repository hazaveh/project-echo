<?php

namespace App\Models\Reddit;

use Illuminate\Database\Eloquent\Model;

class RedditUser extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'contacted',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'contacted' => 'boolean',
    ];
}
