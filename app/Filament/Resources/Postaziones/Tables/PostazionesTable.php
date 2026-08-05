<?php

namespace App\Filament\Resources\Postaziones\Tables;

use App\Models\Postazione;
use App\Support\GestioneEliminaGuardrail;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PostazionesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('nome')->label('Nome')->searchable()->sortable(),
                TextColumn::make('comande_count')->counts('comande')->label('Comande'),
                TextColumn::make('mappature_count')->counts('mappature')->label('Mappature'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->before(function (DeleteAction $action, Postazione $record): void {
                        if ($motivo = GestioneEliminaGuardrail::motivoBloccoPostazione($record)) {
                            Notification::make()->title('Eliminazione bloccata')->body($motivo)->danger()->send();
                            $action->cancel();
                        }
                    }),
            ]);
    }
}
