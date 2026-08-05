<?php

namespace App\Filament\Resources\Seratas\Pages;

use App\Filament\Pages\ApriSerata;
use App\Filament\Resources\Seratas\SerataResource;
use App\Models\Serata;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListSeratas extends ListRecords
{
    protected static string $resource = SerataResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('apri')
                ->label('Apri serata')
                ->icon('heroicon-o-play')
                ->url(ApriSerata::getUrl())
                ->visible(fn (): bool => Serata::corrente() === null),
        ];
    }
}
