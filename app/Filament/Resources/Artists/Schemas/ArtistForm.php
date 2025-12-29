<?php

namespace App\Filament\Resources\Artists\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ArtistForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('prn_artist_id')
                    ->required()
                    ->numeric(),
                TextInput::make('spotify'),
                TextInput::make('instagram'),
                TextInput::make('twitter'),
                TextInput::make('facebook'),
                TextInput::make('youtube'),
                TextInput::make('homepage'),
                TextInput::make('apple_music'),
                DateTimePicker::make('ticketmaster_match_attempted_at'),
            ]);
    }
}
