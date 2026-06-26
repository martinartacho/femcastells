<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\TagResource\Pages;
use App\Models\Tag;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TagResource extends Resource
{
    protected static ?string $model = Tag::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $navigationLabel = 'Etiquetes';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                TextInput::make('name')
                    ->label('Nom')
                    ->required()
                    ->maxLength(100),
                Select::make('type')
                    ->label('Tipus')
                    ->options([
                        'CASTELLERS' => 'Castellers',
                        'EVENTS' => 'Esdeveniments',
                        'POSITIONS' => 'Posicions',
                        'BOARDS' => 'Taules (Pinyes)',
                    ])
                    ->required(),
                TextInput::make('group')
                    ->label('Grup')
                    ->maxLength(50),
                TextInput::make('value')
                    ->label('Valor')
                    ->maxLength(100),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipus')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'CASTELLERS' => 'info',
                        'EVENTS' => 'success',
                        'POSITIONS' => 'warning',
                        'BOARDS' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('group')
                    ->label('Grup')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('value')
                    ->label('Valor')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipus')
                    ->options([
                        'CASTELLERS' => 'Castellers',
                        'EVENTS' => 'Esdeveniments',
                        'POSITIONS' => 'Posicions',
                        'BOARDS' => 'Taules (Pinyes)',
                    ]),
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
            'index' => Pages\ListTags::route('/'),
            'create' => Pages\CreateTag::route('/create'),
            'edit' => Pages\EditTag::route('/{record}/edit'),
        ];
    }
}
