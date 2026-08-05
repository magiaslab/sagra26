<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class GestioneReport extends Page
{
    protected string $view = 'filament.pages.gestione-report';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Operativo';

    protected static ?string $navigationLabel = 'Report';

    protected static ?string $title = 'Report';

    protected static ?int $navigationSort = 4;
}
