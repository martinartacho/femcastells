<?php

declare(strict_types=1);

namespace App\Filament\Resources\EventResource\RelationManagers;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AttendanceRelationManager extends RelationManager
{
    protected static string $relationship = 'attendance';

    protected static ?string $title = 'Assistència';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('casteller_id')
                ->label('Casteller')
                ->relationship('casteller', 'alias')
                ->searchable()
                ->required(),
            Select::make('status')
                ->label('Estat')
                ->options([
                    'ok' => 'Assisteix',
                    'nok' => 'No assisteix',
                    'unknown' => 'Pendent',
                ])
                ->default('unknown')
                ->required(),
            TextInput::make('companions')
                ->label('Acompanyants')
                ->numeric()
                ->default(0)
                ->minValue(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('casteller.alias')
                    ->label('Casteller')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('casteller.name')
                    ->label('Nom')
                    ->formatStateUsing(fn ($record) => $record->casteller->full_name),
                TextColumn::make('status')
                    ->label('Estat')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'ok' => 'Assisteix',
                        'nok' => 'No assisteix',
                        'unknown' => 'Pendent',
                    })
                    ->color(fn (string $state) => match ($state) {
                        'ok' => 'success',
                        'nok' => 'danger',
                        'unknown' => 'warning',
                    }),
                TextColumn::make('companions')
                    ->label('Acompanyants'),
            ])
            ->defaultSort('casteller.alias');
    }
}
