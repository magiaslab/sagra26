<?php

namespace App\Filament\Resources\MenuItems\Schemas;

use App\Models\MenuItem;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MenuItemForm
{
    public static function configure(Schema $schema): Schema
    {
        $aree = collect(MenuItem::AREE_STAMPA)
            ->mapWithKeys(fn (string $a) => [$a => MenuItem::etichettaArea($a)])
            ->all();

        return $schema
            ->components([
                TextInput::make('nome')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255),
                TextInput::make('prezzo')
                    ->label('Prezzo')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefix('€'),
                Select::make('categoria_id')
                    ->label('Categoria')
                    ->relationship('categoria', 'nome')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('area_stampa')
                    ->label('Area stampa')
                    ->options($aree)
                    ->placeholder('Eredita dalla categoria')
                    ->nullable(),
                TextInput::make('stock_default')
                    ->label('Stock default')
                    ->numeric()
                    ->nullable()
                    ->helperText('Vuoto = illimitato'),
                Toggle::make('attivo')
                    ->label('Attivo')
                    ->default(true),
                Toggle::make('piatto_del_giorno')
                    ->label('Piatto del giorno')
                    ->default(false),
                Toggle::make('bar')
                    ->label('Bar')
                    ->default(false),
                Toggle::make('congelato')
                    ->label('Congelato')
                    ->default(false),
                Toggle::make('is_coperto')
                    ->label('Coperto')
                    ->default(false)
                    ->helperText('Al massimo una voce attiva può essere coperto.'),
            ]);
    }
}
