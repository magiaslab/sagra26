<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class GestioneOmaggi extends Page
{
    protected string $view = 'filament.pages.gestione-omaggi';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static string|UnitEnum|null $navigationGroup = 'Operativo';

    protected static ?string $navigationLabel = 'Omaggi';

    protected static ?string $title = 'Omaggi';

    protected static ?int $navigationSort = 6;
}
