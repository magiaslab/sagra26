<?php

namespace App\Filament\Resources\Seratas\Tables;

use App\Models\Serata;
use App\Services\SerataService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SeratasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('data', 'desc')
            ->columns([
                TextColumn::make('data')->label('Data')->date('d/m/Y')->sortable(),
                TextColumn::make('stato')
                    ->label('Stato')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'aperta' ? 'success' : 'gray'),
                TextColumn::make('note')->label('Note')->limit(40)->placeholder('—'),
                TextColumn::make('comande_count')->counts('comande')->label('Comande'),
                TextColumn::make('chiusure_count')->counts('chiusure')->label('Chiusure'),
            ])
            ->recordActions([
                Action::make('riapri')
                    ->label('Riapri')
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn (Serata $record): bool => ! $record->isAperta())
                    ->requiresConfirmation()
                    ->action(function (Serata $record, SerataService $service): void {
                        try {
                            $service->riapri($record);
                            Notification::make()->title('Serata riaperta.')->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('Errore')->body($e->getMessage())->danger()->send();
                        }
                    }),
                Action::make('chiudi')
                    ->label('Chiudi')
                    ->icon('heroicon-o-lock-closed')
                    ->color('warning')
                    ->visible(fn (Serata $record): bool => $record->isAperta())
                    ->requiresConfirmation()
                    ->modalHeading('Chiudi serata')
                    ->modalDescription('I report restano stampabili; per correggere errori potrai riaprire la serata.')
                    ->action(function (Serata $record, SerataService $service): void {
                        try {
                            $service->chiudi($record);
                            Notification::make()->title('Serata chiusa.')->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('Errore')->body($e->getMessage())->danger()->send();
                        }
                    }),
                Action::make('elimina')
                    ->label('Elimina')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn (Serata $record): bool => ! $record->isAperta())
                    ->modalHeading('Elimina serata')
                    ->modalDescription(function (Serata $record): string {
                        $nComande = $record->comande()->count();
                        $nStocks = $record->stocks()->count();
                        $nChiusure = $record->chiusure()->count();
                        $data = $record->data?->format('Y-m-d') ?? '';

                        return "Verranno cancellati definitivamente: {$nComande} comande, {$nStocks} stock, {$nChiusure} chiusure. Digita la data della serata ({$data}) per confermare.";
                    })
                    ->form([
                        TextInput::make('conferma_data')
                            ->label('Digita la data (YYYY-MM-DD)')
                            ->required(),
                    ])
                    ->action(function (Serata $record, array $data, SerataService $service): void {
                        $attesa = $record->data?->format('Y-m-d') ?? '';
                        if (($data['conferma_data'] ?? '') !== $attesa) {
                            Notification::make()
                                ->title('Conferma non valida')
                                ->body('La data digitata non corrisponde a quella della serata.')
                                ->danger()
                                ->send();

                            return;
                        }
                        try {
                            $service->elimina($record);
                            Notification::make()->title('Serata eliminata.')->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('Errore')->body($e->getMessage())->danger()->send();
                        }
                    }),
            ]);
    }
}
