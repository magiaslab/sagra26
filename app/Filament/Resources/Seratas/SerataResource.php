<?php

namespace App\Filament\Resources\Seratas;

use App\Filament\Resources\Seratas\Pages\ListSeratas;
use App\Filament\Resources\Seratas\Schemas\SerataForm;
use App\Filament\Resources\Seratas\Tables\SeratasTable;
use App\Models\Serata;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SerataResource extends Resource
{
    protected static ?string $model = Serata::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Operativo';

    protected static ?string $navigationLabel = 'Serate';

    protected static ?string $modelLabel = 'serata';

    protected static ?string $pluralModelLabel = 'serate';

    protected static ?string $slug = 'serate';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return SerataForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SeratasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSeratas::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
