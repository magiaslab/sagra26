<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * UI operativa serata (stock mid-serata, chiusura con guardrail punti mancanti)
 * riusando il componente Livewire già testato — nessuna logica nuova.
 */
class GestioneSerateOperativo extends Page
{
    protected string $view = 'filament.pages.gestione-serate-operativo';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|UnitEnum|null $navigationGroup = 'Operativo';

    protected static ?string $navigationLabel = 'Stock serata';

    protected static ?string $title = 'Stock e operativo serata';

    protected static ?int $navigationSort = 2;
}
