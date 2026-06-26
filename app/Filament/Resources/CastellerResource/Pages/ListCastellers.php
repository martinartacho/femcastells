<?php

declare(strict_types=1);

namespace App\Filament\Resources\CastellerResource\Pages;

use App\Exports\AttendancePeriodExport;
use App\Exports\CastellerExport;
use App\Filament\Resources\CastellerResource;
use App\Imports\CastellerImport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
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
                ->label('Exporta castellers')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action(function (): BinaryFileResponse {
                    return Excel::download(
                        new CastellerExport(auth()->user()->colla_id),
                        'castellers-' . now()->format('Y-m-d') . '.xlsx'
                    );
                }),

            Action::make('exportAttendance')
                ->label('Exporta assistència')
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

            Action::make('import')
                ->label('Importa FP3')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->color('gray')
                ->form([
                    FileUpload::make('file')
                        ->label('Full de càlcul FP3 (.xlsx)')
                        ->disk('local')
                        ->directory('imports')
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    Excel::import(
                        new CastellerImport(auth()->user()->colla_id),
                        $data['file'],
                        'local'
                    );
                    Notification::make()
                        ->title('Importació completada')
                        ->success()
                        ->send();
                }),

            CreateAction::make(),
        ];
    }
}
