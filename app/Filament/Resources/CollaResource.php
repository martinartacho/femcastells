<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\CollaResource\Pages;
use App\Models\Colla;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CollaResource extends Resource
{
    protected static ?string $model = Colla::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $navigationLabel = 'Colles';

    protected static \UnitEnum|string|null $navigationGroup = 'Administració';

    protected static ?int $navigationSort = 99;

    protected static ?string $slug = 'colles';

    // No escopada per tenant — mostra totes les colles
    protected static bool $isScopedToTenant = false;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identificació')->schema([
                TextInput::make('name')
                    ->label('Nom')
                    ->required()
                    ->maxLength(100),
                TextInput::make('shortname')
                    ->label('Identificador (URL)')
                    ->required()
                    ->maxLength(30)
                    ->regex('/^[a-z0-9_-]+$/')
                    ->helperText('Minúscules, sense espais. Ex: birraires, pitxapins')
                    ->unique(ignoreRecord: true),
            ])->columns(2),

            Section::make('Contacte')->schema([
                TextInput::make('email')
                    ->email()
                    ->maxLength(100),
                TextInput::make('phone')
                    ->label('Telèfon')
                    ->maxLength(20),
                TextInput::make('city')
                    ->label('Municipi')
                    ->maxLength(100),
                TextInput::make('country')
                    ->label('País')
                    ->maxLength(5)
                    ->default('ES'),
            ])->columns(2),

            Section::make('Aparença')->schema([
                ColorPicker::make('color')
                    ->label('Color de la colla'),
                TextInput::make('max_members')
                    ->label('Màx. membres')
                    ->numeric()
                    ->minValue(1),
            ])->columns(2),

            Section::make('Dades de prova')
                ->description('Opcional — afegeix castellers, events i assistència d\'exemple')
                ->schema([
                    Toggle::make('seed_demo_data')
                        ->label('Crear dades mínimes (15 castellers, 3 events, assistències)')
                        ->default(false),
                ])
                ->visibleOn('create'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ColorColumn::make('color')
                    ->label(''),
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('shortname')
                    ->label('ID')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('city')
                    ->label('Municipi')
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->toggleable(),
                TextColumn::make('castellers_count')
                    ->label('Castellers')
                    ->formatStateUsing(fn(int $state, Colla $record): string =>
                        $record->max_members ? "{$state} / {$record->max_members}" : (string) $state
                    )
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Actualitzat')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('name');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount(['castellers' => fn(Builder $q) => $q->withoutGlobalScopes()]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListColles::route('/'),
            'create' => Pages\CreateColla::route('/create'),
            'edit'   => Pages\EditColla::route('/{record}/edit'),
        ];
    }
}
