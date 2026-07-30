<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\CastleTemplateResource\Pages;
use App\Models\CastleTemplate;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class CastleTemplateResource extends Resource
{
    protected static ?string $model = CastleTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $navigationLabel = 'Plantilles';

    protected static \UnitEnum|string|null $navigationGroup = 'Pissarra';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'templates';

    public static function canViewAny(): bool
    {
        return auth()->user()?->canDo('plantilles') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canDo('plantilles', 'write') ?? false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->canDo('plantilles', 'write') ?? false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->canDo('plantilles', 'write') ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('colla_id', auth()->user()?->colla_id);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->schema([
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
                        ->helperText('SVG exportat des d\'Inkscape o similar'),
                ])->columns(3)->columnSpan(2),

                Section::make('Vista prèvia')->schema([
                    Placeholder::make('preview')
                        ->label('')
                        ->content(fn (?CastleTemplate $record): HtmlString => new HtmlString(
                            $record?->positions
                                ? self::renderSvgPreview($record)
                                : '<p style="color:#9ca3af;font-size:12px;text-align:center;padding:24px">Sense posicions encara</p>'
                        )),
                ])->columnSpan(1)->hiddenOn('create'),
            ]),
        ]);
    }

    private static function renderSvgPreview(CastleTemplate $record): string
    {
        $positions = $record->positions;
        if (empty($positions)) {
            return '<p style="color:#9ca3af;font-size:12px;text-align:center;padding:24px">Sense posicions</p>';
        }

        $xs    = array_column($positions, 'cx');
        $ys    = array_column($positions, 'cy');
        $minX  = min($xs); $maxX = max($xs);
        $minY  = min($ys); $maxY = max($ys);
        $dataW = max($maxX - $minX, 1);
        $dataH = max($maxY - $minY, 1);

        $size   = 240;
        $margin = 14;
        $scale  = min(($size - $margin * 2) / $dataW, ($size - $margin * 2) / $dataH);
        $offX   = ($size - $dataW * $scale) / 2 - $minX * $scale;
        $offY   = ($size - $dataH * $scale) / 2 - $minY * $scale;

        $colors = [
            'baix' => '#37474f', 'nucli' => '#ffcdd2',
            'nucli_D' => '#f8bbd0', 'nucli_E' => '#f8bbd0',
            'lateral_D' => '#c8e6c9', 'lateral_E' => '#bbdefb',
            'rengle' => '#fff9c4', 'falca_D' => '#d1c4e9', 'falca_E' => '#d1c4e9',
            'angle_D' => '#ffe0b2', 'angle_E' => '#ffe0b2',
            'Plena' => '#f8bbd0', 'Carregada' => '#bbdefb', 'Buida' => '#c8e6c9',
            'Rengle' => '#fff9c4', 'Descarregada' => '#d1c4e9',
            'Una' => '#f8bbd0', 'Dues' => '#bbdefb', 'Tres' => '#c8e6c9',
            'Quatre' => '#fff9c4', 'Cinc' => '#d1c4e9', 'Sis' => '#ffe0b2', 'Set' => '#b2dfdb',
        ];

        $parts = [];
        foreach ($positions as $pos) {
            $cx    = round($pos['cx'] * $scale + $offX, 1);
            $cy    = round($pos['cy'] * $scale + $offY, 1);
            $rx    = max(1.5, round(($pos['w'] / 2) * $scale, 1));
            $ry    = max(1.0, round(($pos['h'] / 2) * $scale, 1));
            $angle = round((float) ($pos['angle'] ?? 0), 1);
            $fill  = $colors[$pos['grup'] ?? ''] ?? '#e5e7eb';
            $parts[] = "<g transform=\"rotate({$angle},{$cx},{$cy})\"><ellipse cx=\"{$cx}\" cy=\"{$cy}\" rx=\"{$rx}\" ry=\"{$ry}\" fill=\"{$fill}\" stroke=\"#4b5563\" stroke-width=\"0.4\"/></g>";
        }

        $n   = count($positions);
        $svg = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 {$size} {$size}\" style=\"display:block;width:100%;height:auto;border-radius:8px;background:#f9fafb\">"
             . implode('', $parts)
             . "</svg><p style=\"text-align:center;font-size:11px;color:#9ca3af;margin-top:4px\">{$n} posicions</p>";

        return $svg;
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
                TextColumn::make('positions_count')
                    ->label('Nº posicions')
                    ->getStateUsing(fn ($record) => $record->positions ? count($record->positions) : '—'),
                TextColumn::make('updated_at')
                    ->label('Actualitzat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->recordUrl(fn (CastleTemplate $record): string =>
                route('filament.admin.pages.wizard-plantilla', [
                    'tenant' => auth()->user()?->colla?->shortname,
                ]) . '?template=' . $record->id
            )
            ->actions([
                EditAction::make()->label('Reanomena'),
                DeleteAction::make()->label('Elimina'),
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
