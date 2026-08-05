<?php

namespace App\Filament\Resources\PuntoCassas;

use App\Filament\Resources\PuntoCassas\Pages\CreatePuntoCassa;
use App\Filament\Resources\PuntoCassas\Pages\EditPuntoCassa;
use App\Filament\Resources\PuntoCassas\Pages\ListPuntoCassas;
use App\Filament\Resources\PuntoCassas\Schemas\PuntoCassaForm;
use App\Filament\Resources\PuntoCassas\Tables\PuntoCassasTable;
use App\Models\PuntoCassa;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PuntoCassaResource extends Resource
{
    protected static ?string $model = PuntoCassa::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Cassa / Postazioni';

    protected static ?string $navigationLabel = 'Punti cassa';

    protected static ?string $modelLabel = 'punto cassa';

    protected static ?string $pluralModelLabel = 'punti cassa';

    protected static ?string $slug = 'punti-cassa';

    protected static ?int $navigationSort = 11;

    public static function form(Schema $schema): Schema
    {
        return PuntoCassaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PuntoCassasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPuntoCassas::route('/'),
            'create' => CreatePuntoCassa::route('/create'),
            'edit' => EditPuntoCassa::route('/{record}/edit'),
        ];
    }
}
