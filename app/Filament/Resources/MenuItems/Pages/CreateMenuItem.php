<?php

namespace App\Filament\Resources\MenuItems\Pages;

use App\Filament\Resources\MenuItems\MenuItemResource;
use App\Models\MenuItem;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateMenuItem extends CreateRecord
{
    protected static string $resource = MenuItemResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->assertUnicoCopertoAttivo(null, (bool) ($data['is_coperto'] ?? false), (bool) ($data['attivo'] ?? true));
        $data['ordinamento'] = ((int) MenuItem::query()->max('ordinamento')) + 1;

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
