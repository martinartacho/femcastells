<?php

declare(strict_types=1);

namespace App\Filament\Resources\CastleTemplateResource\Pages;

use App\Filament\Resources\CastleTemplateResource;
use App\Services\PinyaMotor;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;

class EditCastleTemplate extends EditRecord
{
    protected static string $resource = CastleTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('editarAlGenerador')
                ->label('Editar al Generador')
                ->icon(Heroicon::OutlinedPencilSquare)
                ->color('gray')
                ->url(fn () => route('filament.admin.pages.generador', [
                    'tenant' => auth()->user()?->colla?->shortname,
                ]) . '?template=' . $this->record->id)
                ->openUrlInNewTab(false),

            Action::make('extractPositions')
                ->label('Extreu posicions del SVG')
                ->icon(Heroicon::OutlinedCog6Tooth)
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Extreure posicions')
                ->modalDescription('Es llegirà el fitxer SVG i s\'extrauran totes les posicions (rects). Qualsevol extracció anterior es sobreescriurà.')
                ->action(function (): void {
                    $record = $this->record;

                    if (!$record->svg_path) {
                        Notification::make()->title('Cal pujar un fitxer SVG primer')->warning()->send();
                        return;
                    }

                    $svgContent = Storage::disk('local')->get($record->svg_path);

                    if (!$svgContent) {
                        Notification::make()->title('Fitxer SVG no trobat')->danger()->send();
                        return;
                    }

                    try {
                        $positions = self::parsePositions($svgContent);
                    } catch (\Exception $e) {
                        Notification::make()->title('Error al llegir el SVG: ' . $e->getMessage())->danger()->send();
                        return;
                    }

                    if (empty($positions)) {
                        Notification::make()->title('No s\'han trobat posicions (rects) al SVG')->warning()->send();
                        return;
                    }

                    $record->update(['positions' => $positions, 'ready' => true]);

                    Notification::make()
                        ->title('Posicions extretes: ' . count($positions))
                        ->success()
                        ->send();
                }),

            Action::make('generateMotor')
                ->label('Genera per motor')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('success')
                ->form([
                    Select::make('num_baixos')
                        ->label('Tipus de castell')
                        ->options([1 => 'Pilar', 2 => 'Torre', 3 => 'Tres', 4 => 'Quatre'])
                        ->required(),
                    TextInput::make('pisos')
                        ->label('Pisos')
                        ->numeric()
                        ->minValue(4)
                        ->maxValue(10)
                        ->required(),
                ])
                ->modalHeading('Genera posicions amb el motor')
                ->modalDescription('Es generaran les posicions geomètriques calculades. Sobreescriurà les posicions actuals.')
                ->action(function (array $data, PinyaMotor $motor): void {
                    $numBaixos = (int) $data['num_baixos'];
                    $pisos     = (int) $data['pisos'];

                    try {
                        $positions = $motor->generate($numBaixos, $pisos);
                    } catch (\InvalidArgumentException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                        return;
                    }

                    $this->record->update(['positions' => $positions, 'ready' => true]);

                    Notification::make()
                        ->title('Generades ' . count($positions) . ' posicions (' . $motor->nomCastell($numBaixos, $pisos) . ')')
                        ->success()
                        ->send();
                }),

            DeleteAction::make(),
        ];
    }

    private static function parsePositions(string $svgContent): array
    {
        $xml = new \SimpleXMLElement($svgContent);
        $xml->registerXPathNamespace('svg', 'http://www.w3.org/2000/svg');
        $rects = $xml->xpath('//svg:rect') ?: $xml->xpath('//rect');

        $positions = [];
        foreach ($rects as $rect) {
            $attrs = (array) $rect->attributes();
            $a     = $attrs['@attributes'] ?? [];

            $x         = (float) ($a['x'] ?? 0);
            $y         = (float) ($a['y'] ?? 0);
            $w         = (float) ($a['width'] ?? 0);
            $h         = (float) ($a['height'] ?? 0);
            $id        = (string) ($a['id'] ?? '');
            $transform = (string) ($a['transform'] ?? '');

            $matrix = [1, 0, 0, 1, 0, 0];
            if (preg_match('/matrix\(([^)]+)\)/', $transform, $m)) {
                $matrix = array_map('floatval', preg_split('/[\s,]+/', trim($m[1])));
            }

            [$ma, $mb, $mc, $md, $me, $mf] = array_pad($matrix, 6, 0);

            $lx = $x + $w / 2;
            $ly = $y + $h / 2;

            $positions[] = [
                'id'     => $id,
                'x'      => round($x, 2),
                'y'      => round($y, 2),
                'w'      => round($w, 2),
                'h'      => round($h, 2),
                'cx'     => round($ma * $lx + $mc * $ly + $me, 2),
                'cy'     => round($mb * $lx + $md * $ly + $mf, 2),
                'angle'  => round(rad2deg(atan2($mb, $ma)), 1),
                'matrix' => array_map(fn ($v) => round($v, 4), $matrix),
            ];
        }

        return $positions;
    }
}
