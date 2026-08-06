<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\HelpItemResource\Pages;
use App\Models\HelpItem;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class HelpItemResource extends Resource
{
    protected static ?string $model = HelpItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static ?string $navigationLabel = 'Ajuda (FAQ)';

    protected static \UnitEnum|string|null $navigationGroup = 'Administració';

    protected static ?int $navigationSort = 60;

    protected static bool $isScopedToTenant = false;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super-admin') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                TextInput::make('category')
                    ->label('Categoria')
                    ->required()
                    ->maxLength(100)
                    ->datalist(fn () => HelpItem::distinct()->orderBy('category')->pluck('category')->toArray())
                    ->helperText('Escriu una categoria existent o una de nova.'),

                TextInput::make('question')
                    ->label('Pregunta')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Textarea::make('answer')
                    ->label('Resposta')
                    ->required()
                    ->rows(5)
                    ->columnSpanFull(),

                TextInput::make('sort')
                    ->label('Ordre')
                    ->numeric()
                    ->default(0)
                    ->helperText('Menor número = apareix primer dins la categoria.'),

                Toggle::make('visible')
                    ->label('Visible')
                    ->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category')
                    ->label('Categoria')
                    ->sortable()
                    ->badge()
                    ->searchable(),
                TextColumn::make('question')
                    ->label('Pregunta')
                    ->limit(60)
                    ->searchable(),
                TextColumn::make('sort')
                    ->label('Ordre')
                    ->sortable(),
                IconColumn::make('visible')
                    ->label('Visible')
                    ->boolean(),
            ])
            ->defaultSort('category')
            ->filters([
                SelectFilter::make('category')
                    ->label('Categoria')
                    ->options(fn () => HelpItem::distinct()->orderBy('category')->pluck('category', 'category')->toArray()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListHelpItems::route('/'),
            'create' => Pages\CreateHelpItem::route('/create'),
            'edit'   => Pages\EditHelpItem::route('/{record}/edit'),
        ];
    }
}
