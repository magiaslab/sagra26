<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class GestioneImpostazioni extends Page
{
    protected string $view = 'filament.pages.gestione-impostazioni';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Sistema';

    protected static ?string $navigationLabel = 'Impostazioni';

    protected static ?string $title = 'Impostazioni';

    protected static ?int $navigationSort = 20;
}
