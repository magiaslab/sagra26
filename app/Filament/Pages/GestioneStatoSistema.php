<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class GestioneStatoSistema extends Page
{
    protected string $view = 'filament.pages.gestione-stato-sistema';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static string|UnitEnum|null $navigationGroup = 'Sistema';

    protected static ?string $navigationLabel = 'Stato sistema';

    protected static ?string $title = 'Stato sistema';

    protected static ?int $navigationSort = 21;
}
