<?php

declare(strict_types=1);

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Models\OrderItem;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Productes de la comanda';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product_name')
                    ->label('Producte'),

                TextColumn::make('variant_name')
                    ->label('Variant'),

                TextColumn::make('quantity')
                    ->label('Quantitat'),

                TextColumn::make('unit_price')
                    ->label('Preu unitat')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2) . ' €'),

                TextColumn::make('tax_rate')
                    ->label('IVA')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2) . ' %'),

                TextColumn::make('line_total')
                    ->label('Total línia')
                    ->state(fn (OrderItem $record) => number_format($record->lineTotal(), 2) . ' €'),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
