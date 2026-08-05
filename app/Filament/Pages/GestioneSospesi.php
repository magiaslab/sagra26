<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class GestioneSospesi extends Page
{
    protected string $view = 'filament.pages.gestione-sospesi';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'Operativo';

    protected static ?string $navigationLabel = 'Sospesi';

    protected static ?string $title = 'Sospesi';

    protected static ?int $navigationSort = 5;
}
