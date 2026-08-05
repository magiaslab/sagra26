<?php

namespace App\Filament\Resources\MenuItems\Pages;

use App\Filament\Resources\MenuItems\MenuItemResource;
use App\Models\MenuItem;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditMenuItem extends EditRecord
{
    protected static string $resource = MenuItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->assertUnicoCopertoAttivo(
            (int) $this->record->getKey(),
            (bool) ($data['is_coperto'] ?? false),
            (bool) ($data['attivo'] ?? true),
        );

        return $data;
    }

    private function assertUnicoCopertoAttivo(?int $escludiId, bool $isCoperto, bool $attivo): void
    {
        if (! $isCoperto || ! $attivo) {
            return;
        }

        $altro = MenuItem::query()
            ->where('is_coperto', true)
            ->where('attivo', true)
            ->when($escludiId, fn ($q) => $q->where('id', '!=', $escludiId))
            ->exists();

        if ($altro) {
            throw ValidationException::withMessages([
                'data.is_coperto' => 'Esiste già una voce attiva marcata come Coperto.',
            ]);
        }
    }
}
