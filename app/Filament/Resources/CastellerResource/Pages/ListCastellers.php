<?php

declare(strict_types=1);

namespace App\Filament\Resources\CastellerResource\Pages;

use App\Filament\Resources\CastellerResource;
use App\Imports\CastellerImport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Maatwebsite\Excel\Facades\Excel;

class ListCastellers extends ListRecords
{
    protected static string $resource = CastellerResource::class;

    protected function getHeaderActions(): array
    {
        return [
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
