<?php

namespace App\Filament\Resources\PuntoCassas\Tables;

use App\Models\PuntoCassa;
use App\Support\GestioneEliminaGuardrail;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PuntoCassasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('nome')->label('Nome')->searchable()->sortable(),
                IconColumn::make('attivo')->label('Attivo')->boolean(),
                TextColumn::make('comande_count')->counts('comande')->label('Comande'),
                TextColumn::make('chiusure_count')->counts('chiusure')->label('Chiusure'),
                TextColumn::make('mappature_count')->counts('mappature')->label('Mappature'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->before(function (DeleteAction $action, PuntoCassa $record): void {
                        if ($motivo = GestioneEliminaGuardrail::motivoBloccoPuntoCassa($record)) {
                            Notification::make()->title('Eliminazione bloccata')->body($motivo)->danger()->send();
                            $action->cancel();
                        }
                    }),
            ]);
    }
}
