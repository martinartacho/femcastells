<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\PeriodResource\Pages;
use App\Models\Period;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PeriodResource extends Resource
{
    protected static ?string $model = Period::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static ?string $navigationLabel = 'Períodes';

    protected static \UnitEnum|string|null $navigationGroup = 'Administració';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return auth()->user()?->canDo('config') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nom')
                ->required()
                ->maxLength(50)
                ->placeholder('Temporada 2026-27'),
            DatePicker::make('start_date')
                ->label('Inici')
                ->required()
                ->displayFormat('d/m/Y'),
            DatePicker::make('end_date')
                ->label('Fi')
                ->required()
                ->displayFormat('d/m/Y')
                ->after('start_date'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('start_date')
                    ->label('Inici')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('Fi')
                    ->date('d/m/Y')
                    ->sortable(),
                IconColumn::make('active')
                    ->label('Actiu')
                    ->state(fn (Period $record): bool => $record->isActive())
                    ->boolean(),
            ])
            ->defaultSort('start_date', 'desc');
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('colla_id', filament()->getTenant()->id);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPeriods::route('/'),
            'create' => Pages\CreatePeriod::route('/create'),
            'edit'   => Pages\EditPeriod::route('/{record}/edit'),
        ];
    }
}
