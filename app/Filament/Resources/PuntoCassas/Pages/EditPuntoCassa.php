<?php

namespace App\Filament\Resources\PuntoCassas\Pages;

use App\Filament\Resources\PuntoCassas\PuntoCassaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPuntoCassa extends EditRecord
{
    protected static string $resource = PuntoCassaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
