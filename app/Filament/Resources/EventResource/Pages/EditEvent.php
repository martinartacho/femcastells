<?php

declare(strict_types=1);

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use App\Imports\AttendanceImport;
use App\Models\Event;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openBoard')
                ->label('Pissarra')
                ->icon(Heroicon::OutlinedTableCells)
                ->color('info')
                ->url(fn () => '/admin/' . auth()->user()->colla->shortname . '/pissarra?selectedEventId=' . $this->record->id),
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
            Action::make('editSeries')
                ->label('Editar tota la sèrie')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('warning')
                ->visible(fn () => filled($this->record->recurring_group_id))
                ->form([
                    Select::make('scope')
                        ->label('Aplicar a')
                        ->options([
                            'all'    => 'Tots els events de la sèrie',
                            'future' => 'Només els futurs (inclòs aquest)',
                        ])
                        ->default('future')
                        ->required()
                        ->native(false),
                    TextInput::make('address')
                        ->label('Nova adreça (buit = no canviar)')
                        ->maxLength(255),
                    TextInput::make('start_time')
                        ->label('Nova hora (buit = no canviar)')
                        ->placeholder('20:00')
                        ->regex('/^(\d{2}:\d{2})?$/'),
                    Select::make('day_shift')
                        ->label('Canviar dia de la setmana (opcional)')
                        ->options([
                            ''  => '— No canviar —',
                            1   => 'Dilluns',
                            2   => 'Dimarts',
                            3   => 'Dimecres',
                            4   => 'Dijous',
                            5   => 'Divendres',
                            6   => 'Dissabte',
                            0   => 'Diumenge',
                        ])
                        ->native(false),
                    Toggle::make('visibility')
                        ->label('Visible als membres')
                        ->default(fn () => $this->record->visibility),
                ])
                ->action(function (array $data): void {
                    $query = Event::where('recurring_group_id', $this->record->recurring_group_id);

                    if ($data['scope'] === 'future') {
                        $query->where('start_date', '>=', $this->record->start_date);
                    }

                    $update = ['visibility' => $data['visibility']];

                    if (filled($data['address'])) {
                        $update['address'] = $data['address'];
                    }

                    $newHour    = null;
                    $newMinute  = null;
                    $newDayOfWeek = filled($data['day_shift']) ? (int) $data['day_shift'] : null;

                    if (filled($data['start_time'])) {
                        [$newHour, $newMinute] = explode(':', $data['start_time']);
                    }

                    $needsDateUpdate = $newHour !== null || $newDayOfWeek !== null;

                    if ($needsDateUpdate) {
                        foreach ($query->get() as $event) {
                            $date = Carbon::parse($event->start_date);
                            if ($newDayOfWeek !== null) {
                                $date = $date->next($newDayOfWeek)->setTime($date->hour, $date->minute);
                            }
                            if ($newHour !== null) {
                                $date->setTime((int) $newHour, (int) $newMinute);
                            }
                            $event->update([...$update, 'start_date' => $date]);
                        }
                    } else {
                        $query->update($update);
                    }

                    Notification::make()
                        ->title('Sèrie actualitzada')
                        ->success()
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }
}
