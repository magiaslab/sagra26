<?php

namespace App\Filament\Resources\PostazionePuntoCassas\Pages;

use App\Filament\Resources\PostazionePuntoCassas\PostazionePuntoCassaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPostazionePuntoCassas extends ListRecords
{
    protected static string $resource = PostazionePuntoCassaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
