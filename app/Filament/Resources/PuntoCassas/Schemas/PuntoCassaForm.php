<?php

namespace App\Filament\Resources\PuntoCassas\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PuntoCassaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nome')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255),
                Toggle::make('attivo')
                    ->label('Attivo')
                    ->default(true),
            ]);
    }
}
