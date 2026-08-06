<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\SuggerimentResource\Pages;
use App\Models\Suggeriment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SuggerimentResource extends Resource
{
    protected static ?string $model = Suggeriment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleBottomCenterText;

    protected static ?string $navigationLabel = 'Suggeriments';

    protected static \UnitEnum|string|null $navigationGroup = 'Administració';

    protected static ?int $navigationSort = 55;

    protected static bool $isScopedToTenant = false;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole(['admin', 'super-admin']) ?? false;
    }

    public static function canCreate(): bool { return false; }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool { return false; }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool { return false; }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['casteller', 'colla']);
        if (!auth()->user()?->hasRole('super-admin')) {
            $query->where('colla_id', auth()->user()?->colla_id);
        }
        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('casteller.alias')
                    ->label('Casteller')
                    ->searchable(),
                TextColumn::make('colla.name')
                    ->label('Colla')
                    ->visible(fn () => auth()->user()?->hasRole('super-admin') ?? false),
                TextColumn::make('text')
                    ->label('Missatge')
                    ->limit(80)
                    ->wrap(),
            ])
            ->recordUrl(null);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuggeriments::route('/'),
        ];
    }
}
