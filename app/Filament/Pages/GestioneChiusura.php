<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class GestioneChiusura extends Page
{
    protected string $view = 'filament.pages.gestione-chiusura';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static string|UnitEnum|null $navigationGroup = 'Operativo';

    protected static ?string $navigationLabel = 'Chiusura cassa';

    protected static ?string $title = 'Chiusura cassa';

    protected static ?int $navigationSort = 3;
}
