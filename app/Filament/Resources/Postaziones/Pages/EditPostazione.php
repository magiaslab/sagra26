<?php

namespace App\Filament\Resources\Postaziones\Pages;

use App\Filament\Resources\Postaziones\PostazioneResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPostazione extends EditRecord
{
    protected static string $resource = PostazioneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
