<?php

namespace App\Filament\Resources\MenuItems\Pages;

use App\Filament\Resources\MenuItems\MenuItemResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMenuItems extends ListRecords
{
    protected static string $resource = MenuItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('facsimile')
                ->label('Stampa facsimile')
                ->icon('heroicon-o-printer')
                ->url(fn (): string => route('gestione.menu.facsimile', ['print' => 1], absolute: false))
                ->openUrlInNewTab(),
            CreateAction::make(),
        ];
    }
}
