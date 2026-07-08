<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\NoticiaResource\Pages;
use App\Models\Noticia;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class NoticiaResource extends Resource
{
    protected static ?string $model = Noticia::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static ?string $navigationLabel = 'Notícies';

    protected static \UnitEnum|string|null $navigationGroup = 'Administració';

    protected static ?int $navigationSort = 50;

    protected static bool $isScopedToTenant = false;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        if (!auth()->user()?->hasRole('super-admin')) {
            $query->where('colla_id', auth()->user()?->colla_id);
        }
        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Contingut')->schema([
                TextInput::make('title')
                    ->label('Títol')
                    ->required()
                    ->maxLength(200)
                    ->columnSpanFull(),
                TextInput::make('summary')
                    ->label('Resum')
                    ->maxLength(300)
                    ->columnSpanFull(),
                RichEditor::make('body')
                    ->label('Cos')
                    ->required()
                    ->columnSpanFull(),
            ]),

            Section::make('Classificació')->schema([
                Select::make('labels')
                    ->label('Labels')
                    ->multiple()
                    ->options(Noticia::LABELS)
                    ->required(),
                TextInput::make('version')
                    ->label('Versió')
                    ->placeholder('1.2.0')
                    ->maxLength(20),
                DateTimePicker::make('published_at')
                    ->label('Publicat el')
                    ->helperText('Deixa buit per a esborrany'),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Títol')
                    ->searchable()
                    ->limit(60),
                TextColumn::make('labels')
                    ->label('Labels')
                    ->badge()
                    ->formatStateUsing(fn($state) => Noticia::LABELS[$state] ?? $state),
                TextColumn::make('version')
                    ->label('Versió')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('published_at')
                    ->label('Publicat')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->placeholder('Esborrany'),
                TextColumn::make('colla.name')
                    ->label('Colla')
                    ->placeholder('Global')
                    ->toggleable(),
            ])
            ->defaultSort('published_at', 'desc')
            ->filters([
                SelectFilter::make('labels')
                    ->label('Label')
                    ->options(Noticia::LABELS)
                    ->query(fn($query, $data) => $data['value']
                        ? $query->whereJsonContains('labels', $data['value'])
                        : $query),
            ])
            ->actions([]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListNoticies::route('/'),
            'create' => Pages\CreateNoticia::route('/create'),
            'edit'   => Pages\EditNoticia::route('/{record}/edit'),
        ];
    }
}
