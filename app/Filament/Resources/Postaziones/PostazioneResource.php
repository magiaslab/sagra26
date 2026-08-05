<?php

namespace App\Filament\Resources\Postaziones;

use App\Filament\Resources\Postaziones\Pages\CreatePostazione;
use App\Filament\Resources\Postaziones\Pages\EditPostazione;
use App\Filament\Resources\Postaziones\Pages\ListPostaziones;
use App\Filament\Resources\Postaziones\Schemas\PostazioneForm;
use App\Filament\Resources\Postaziones\Tables\PostazionesTable;
use App\Models\Postazione;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PostazioneResource extends Resource
{
    protected static ?string $model = Postazione::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static string|UnitEnum|null $navigationGroup = 'Cassa / Postazioni';

    protected static ?string $navigationLabel = 'Postazioni';

    protected static ?string $modelLabel = 'postazione';

    protected static ?string $pluralModelLabel = 'postazioni';

    protected static ?string $slug = 'postazioni';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return PostazioneForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PostazionesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPostaziones::route('/'),
            'create' => CreatePostazione::route('/create'),
            'edit' => EditPostazione::route('/{record}/edit'),
        ];
    }
}
