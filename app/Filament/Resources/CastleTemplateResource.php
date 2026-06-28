<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\CastleTemplateResource\Pages;
use App\Models\CastleTemplate;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CastleTemplateResource extends Resource
{
    protected static ?string $model = CastleTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $navigationLabel = 'Plantilles';

    protected static \UnitEnum|string|null $navigationGroup = 'Pissarra';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'templates';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('colla_id', auth()->user()?->colla_id);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Plantilla de castell')->schema([
                TextInput::make('name')
                    ->label('Nom')
                    ->required()
                    ->maxLength(100)
                    ->placeholder('Ex: 3 de 6, 4 de 7...'),
                TextInput::make('key')
                    ->label('Identificador')
                    ->required()
                    ->maxLength(30)
                    ->regex('/^[a-zA-Z0-9_-]+$/')
                    ->helperText('Sense espais ni accents. Ex: 3d6, 4a_Santi')
                    ->placeholder('3d6'),
                FileUpload::make('svg_path')
                    ->label('Fitxer SVG')
                    ->disk('local')
                    ->directory('castle-svgs')
                    ->acceptedFileTypes(['image/svg+xml', 'text/plain', 'text/xml', 'application/xml'])
                    ->maxSize(5120)
                    ->helperText('SVG exportat des d\'Inkscape o similar')
                    ->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                TextColumn::make('key')
                    ->label('Identificador')
                    ->badge()
                    ->color('gray'),
                IconColumn::make('ready')
                    ->label('Posicions OK')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('warning'),
                TextColumn::make('positions')
                    ->label('Nº posicions')
                    ->formatStateUsing(fn ($state) => $state ? count($state) : '—'),
                TextColumn::make('updated_at')
                    ->label('Actualitzat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('name');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCastleTemplates::route('/'),
            'create' => Pages\CreateCastleTemplate::route('/create'),
            'edit'   => Pages\EditCastleTemplate::route('/{record}/edit'),
        ];
    }
}
