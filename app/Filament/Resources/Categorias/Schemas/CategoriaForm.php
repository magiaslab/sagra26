<?php

namespace App\Filament\Resources\Categorias\Schemas;

use App\Models\MenuItem;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CategoriaForm
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
                Select::make('area_stampa')
                    ->label('Area stampa')
                    ->options($aree)
                    ->required(),
                Toggle::make('is_bevande')
                    ->label('Categoria bevande')
                    ->default(false),
            ]);
    }
}
