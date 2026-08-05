<?php

namespace App\Filament\Resources\PostazionePuntoCassas\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PostazionePuntoCassasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('valido_da', 'desc')
            ->columns([
                TextColumn::make('postazione.nome')->label('Postazione')->searchable()->sortable(),
                TextColumn::make('puntoCassa.nome')->label('Punto cassa')->searchable()->sortable(),
                TextColumn::make('valido_da')->label('Valido da')->date('d/m/Y')->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
