<?php

namespace App\Filament\Resources\Postaziones\Pages;

use App\Filament\Resources\Postaziones\PostazioneResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPostaziones extends ListRecords
{
    protected static string $resource = PostazioneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
