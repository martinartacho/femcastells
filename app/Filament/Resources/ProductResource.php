<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\{BulkActionGroup, DeleteAction, DeleteBulkAction, EditAction};
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?string $navigationLabel = 'Productes';

    protected static \UnitEnum|string|null $navigationGroup = 'Botiga';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->user()?->canDo('botiga') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canDo('botiga', 'write') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->canDo('botiga', 'write') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->canDo('botiga', 'write') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Producte')->schema([
                TextInput::make('name')
                    ->label('Nom')
                    ->required()
                    ->maxLength(150)
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->label('Descripció')
                    ->rows(3)
                    ->columnSpanFull(),

                FileUpload::make('image')
                    ->label('Imatge')
                    ->image()
                    ->disk('public')
                    ->directory('products')
                    ->maxSize(2048)
                    ->imagePreviewHeight('120'),

                Toggle::make('active')
                    ->label('Actiu (visible a la botiga)')
                    ->default(true),

                Toggle::make('members_only')
                    ->label('Exclusiu per a socis/castellers')
                    ->helperText('Si s\'activa, només el veuran els membres amb login al portal membre.')
                    ->default(false),
            ])->columns(1),

            Section::make('Variants')
                ->description('Cada producte necessita com a mínim una variant (p.ex. "Estàndard" si no hi ha talles ni colors).')
                ->schema([
                    Repeater::make('variants')
                        ->relationship()
                        ->hiddenLabel()
                        ->schema([
                            TextInput::make('name')
                                ->label('Nom de la variant')
                                ->helperText('P.ex. "Talla M", "Vermell", o "Estàndard"')
                                ->required()
                                ->maxLength(100),

                            TextInput::make('sku')
                                ->label('Referència (SKU)')
                                ->maxLength(50),

                            TextInput::make('price')
                                ->label('Preu públic')
                                ->numeric()
                                ->prefix('€')
                                ->minValue(0)
                                ->required(),

                            TextInput::make('member_price')
                                ->label('Preu soci/casteller')
                                ->numeric()
                                ->prefix('€')
                                ->minValue(0)
                                ->helperText('Buit = mateix preu que el públic'),

                            TextInput::make('tax_rate')
                                ->label('IVA')
                                ->numeric()
                                ->suffix('%')
                                ->minValue(0)
                                ->maxValue(100)
                                ->default(0)
                                ->helperText('0% si és un servei exempt; consulta el teu gestor per a mercaderia.')
                                ->required(),

                            TextInput::make('stock')
                                ->label('Estoc')
                                ->numeric()
                                ->minValue(0)
                                ->helperText('Buit = sense control d\'estoc'),

                            Toggle::make('active')
                                ->label('Activa')
                                ->default(true),
                        ])
                        ->columns(4)
                        ->defaultItems(1)
                        ->addActionLabel('Afegir variant')
                        ->reorderable(false),
                ]),
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

                TextColumn::make('variants_count')
                    ->label('Variants')
                    ->counts('variants'),

                TextColumn::make('variants_min_price')
                    ->label('Preu')
                    ->state(function (Product $record) {
                        $min = $record->variants->min('price');
                        $max = $record->variants->max('price');

                        if ($min === null) {
                            return '—';
                        }

                        return $min === $max
                            ? number_format((float) $min, 2) . ' €'
                            : number_format((float) $min, 2) . '–' . number_format((float) $max, 2) . ' €';
                    }),

                IconColumn::make('active')
                    ->label('Actiu')
                    ->boolean(),

                IconColumn::make('members_only')
                    ->label('Exclusiu socis')
                    ->boolean(),
            ])
            ->defaultSort('name')
            ->filters([])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit'   => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
