<?php

namespace App\Filament\Resources\PuntoCassas\Pages;

use App\Filament\Resources\PuntoCassas\PuntoCassaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPuntoCassas extends ListRecords
{
    protected static string $resource = PuntoCassaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
