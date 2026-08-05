<?php

namespace App\Filament\Resources\Categorias\Tables;

use App\Models\MenuItem;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CategoriasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('ordinamento')
            ->reorderable('ordinamento')
            ->columns([
                TextColumn::make('ordinamento')->label('#')->sortable(),
                TextColumn::make('nome')->label('Nome')->searchable()->sortable(),
                TextColumn::make('area_stampa')
                    ->label('Area stampa')
                    ->formatStateUsing(fn (?string $state) => MenuItem::etichettaArea($state)),
                IconColumn::make('is_bevande')->label('Bevande')->boolean(),
                TextColumn::make('menu_items_count')
                    ->counts('menuItems')
                    ->label('Voci'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
