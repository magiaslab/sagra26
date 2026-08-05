<?php

namespace App\Filament\Resources\MenuItems\Tables;

use App\Models\MenuItem;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class MenuItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('ordinamento')
            ->reorderable('ordinamento')
            ->columns([
                TextColumn::make('ordinamento')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('nome')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('categoria.nome')
                    ->label('Categoria')
                    ->sortable(),
                TextColumn::make('prezzo')
                    ->label('Prezzo')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('area_stampa')
                    ->label('Area')
                    ->formatStateUsing(fn (?string $state, MenuItem $record) => MenuItem::etichettaArea($record->areaStampaEffettiva()))
                    ->description(fn (MenuItem $record) => $record->area_stampa === null ? 'ereditata' : 'override'),
                TextColumn::make('stock_default')
                    ->label('Stock')
                    ->placeholder('∞'),
                IconColumn::make('attivo')->boolean()->label('Attivo'),
                IconColumn::make('piatto_del_giorno')->boolean()->label('PDG'),
                IconColumn::make('bar')->boolean()->label('Bar'),
                IconColumn::make('congelato')->boolean()->label('Cong.'),
                IconColumn::make('is_coperto')->boolean()->label('Cop.'),
            ])
            ->filters([
                SelectFilter::make('categoria_id')
                    ->label('Categoria')
                    ->relationship('categoria', 'nome'),
                TernaryFilter::make('attivo')->label('Attivo'),
                TernaryFilter::make('bar')->label('Bar'),
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
