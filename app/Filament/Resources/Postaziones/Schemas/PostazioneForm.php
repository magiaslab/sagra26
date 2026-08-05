<?php

namespace App\Filament\Resources\Postaziones\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PostazioneForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nome')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255),
            ]);
    }
}
