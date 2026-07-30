<?php

declare(strict_types=1);

namespace App\Filament\Resources\CastellerResource\Pages;

use App\Exports\AttendancePeriodExport;
use App\Exports\CastellerExport;
use App\Filament\Resources\CastellerResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ListCastellers extends ListRecords
{
    protected static string $resource = CastellerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportCastellers')
                ->label('Exportar Excel')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action(function (): BinaryFileResponse {
                    return Excel::download(
                        new CastellerExport(auth()->user()->colla_id),
                        'castellers-' . now()->format('Y-m-d') . '.xlsx'
                    );
                }),

            Action::make('exportAttendance')
                ->label('Exportar assistència')
                ->icon(Heroicon::OutlinedTableCells)
                ->color('gray')
                ->form([
                    DatePicker::make('from_date')
                        ->label('Des de')
                        ->displayFormat('d/m/Y')
                        ->default(now()->startOfMonth())
                        ->required(),
                    DatePicker::make('to_date')
                        ->label('Fins a')
                        ->displayFormat('d/m/Y')
                        ->default(now()->endOfMonth())
                        ->required(),
                ])
                ->action(function (array $data): BinaryFileResponse {
                    return Excel::download(
                        new AttendancePeriodExport(
                            auth()->user()->colla_id,
                            $data['from_date'],
                            $data['to_date'],
                        ),
                        'assistencia-' . $data['from_date'] . '-' . $data['to_date'] . '.xlsx'
                    );
                }),

            Action::make('profiles')
                ->label('Perfils físics')
                ->icon(Heroicon::OutlinedChartBar)
                ->color('gray')
                ->url(CastellerResource::getUrl('profiles')),

            Action::make('import')
                ->label('Importar CSV')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->color('gray')
                ->url(CastellerResource::getUrl('import')),

            CreateAction::make(),
        ];
    }
}
