<?php

namespace App\Filament\Resources\Seratas\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class SerataForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('data')
                    ->required(),
                TextInput::make('stato')
                    ->required()
                    ->default('aperta'),
                Textarea::make('note')
                    ->columnSpanFull(),
            ]);
    }
}
