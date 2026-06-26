<?php

declare(strict_types=1);

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use App\Imports\AttendanceImport;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Maatwebsite\Excel\Facades\Excel;

class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importAttendance')
                ->label('Importa assistència CSV')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->color('gray')
                ->form([
                    FileUpload::make('file')
                        ->label('CSV exportat de FP3')
                        ->disk('local')
                        ->directory('imports')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv'])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    Excel::import(
                        new AttendanceImport(
                            $this->record->id,
                            auth()->user()->colla_id,
                        ),
                        $data['file'],
                        'local'
                    );
                    Notification::make()
                        ->title('Assistència importada')
                        ->success()
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }
}
