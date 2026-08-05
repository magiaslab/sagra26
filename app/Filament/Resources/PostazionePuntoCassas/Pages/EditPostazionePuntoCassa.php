<?php

namespace App\Filament\Resources\PostazionePuntoCassas\Pages;

use App\Filament\Resources\PostazionePuntoCassas\PostazionePuntoCassaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPostazionePuntoCassa extends EditRecord
{
    protected static string $resource = PostazionePuntoCassaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
