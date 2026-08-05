<?php

namespace App\Filament\Resources\PostazionePuntoCassas;

use App\Filament\Resources\PostazionePuntoCassas\Pages\CreatePostazionePuntoCassa;
use App\Filament\Resources\PostazionePuntoCassas\Pages\EditPostazionePuntoCassa;
use App\Filament\Resources\PostazionePuntoCassas\Pages\ListPostazionePuntoCassas;
use App\Filament\Resources\PostazionePuntoCassas\Schemas\PostazionePuntoCassaForm;
use App\Filament\Resources\PostazionePuntoCassas\Tables\PostazionePuntoCassasTable;
use App\Models\PostazionePuntoCassa;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PostazionePuntoCassaResource extends Resource
{
    protected static ?string $model = PostazionePuntoCassa::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|UnitEnum|null $navigationGroup = 'Cassa / Postazioni';

    protected static ?string $navigationLabel = 'Mappatura';

    protected static ?string $modelLabel = 'mappatura';

    protected static ?string $pluralModelLabel = 'mappature';

    protected static ?string $slug = 'mappature';

    protected static ?int $navigationSort = 12;

    public static function form(Schema $schema): Schema
    {
        return PostazionePuntoCassaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PostazionePuntoCassasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPostazionePuntoCassas::route('/'),
            'create' => CreatePostazionePuntoCassa::route('/create'),
            'edit' => EditPostazionePuntoCassa::route('/{record}/edit'),
        ];
    }
}
