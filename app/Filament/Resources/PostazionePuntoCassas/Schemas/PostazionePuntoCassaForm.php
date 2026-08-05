<?php

namespace App\Filament\Resources\PostazionePuntoCassas\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class PostazionePuntoCassaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('postazione_id')
                    ->label('Postazione')
                    ->relationship('postazione', 'nome')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('punto_cassa_id')
                    ->label('Punto cassa')
                    ->relationship('puntoCassa', 'nome')
                    ->searchable()
                    ->preload()
                    ->required(),
                DatePicker::make('valido_da')
                    ->label('Valido da')
                    ->required()
                    ->default(now()->toDateString()),
            ]);
    }
}
