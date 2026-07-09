<?php

declare(strict_types=1);

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use App\Models\Event;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ListEvents extends ListRecords
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('recurring')
                ->label('Crear recurrents')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('info')
                ->form([
                    Select::make('type')
                        ->label('Tipus')
                        ->options([1 => 'Assaig', 2 => 'Actuació', 3 => 'Activitat'])
                        ->default(1)
                        ->required()
                        ->native(false),
                    TextInput::make('name')
                        ->label('Nom (prefix)')
                        ->placeholder('Assaig')
                        ->maxLength(200),
                    TextInput::make('address')
                        ->label('Adreça')
                        ->maxLength(255),
                    TextInput::make('start_time')
                        ->label('Hora inici')
                        ->placeholder('20:00')
                        ->regex('/^\d{2}:\d{2}$/')
                        ->required(),
                    Select::make('repetition_type')
                        ->label('Repetició')
                        ->options([
                            'weekly'    => 'Dies de la setmana',
                            'every_x'   => 'Cada N dies',
                            'monthly'   => 'Cada mes (mateix dia)',
                        ])
                        ->default('weekly')
                        ->required()
                        ->native(false)
                        ->live(),
                    CheckboxList::make('days_of_week')
                        ->label('Dies de la setmana')
                        ->options([
                            1 => 'Dilluns',
                            2 => 'Dimarts',
                            3 => 'Dimecres',
                            4 => 'Dijous',
                            5 => 'Divendres',
                            6 => 'Dissabte',
                            0 => 'Diumenge',
                        ])
                        ->columns(4)
                        ->visible(fn ($get) => $get('repetition_type') === 'weekly')
                        ->required(fn ($get) => $get('repetition_type') === 'weekly'),
                    TextInput::make('interval_days')
                        ->label('Cada quants dies')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(365)
                        ->default(15)
                        ->visible(fn ($get) => $get('repetition_type') === 'every_x')
                        ->required(fn ($get) => $get('repetition_type') === 'every_x'),
                    DatePicker::make('date_from')
                        ->label('Des de')
                        ->displayFormat('d/m/Y')
                        ->required(),
                    DatePicker::make('date_to')
                        ->label('Fins a')
                        ->displayFormat('d/m/Y')
                        ->required(),
                    Toggle::make('visibility')
                        ->label('Visible als membres')
                        ->default(true),
                ])
                ->action(function (array $data): void {
                    $collaId  = filament()->getTenant()->id;
                    $groupId  = (string) Str::uuid();
                    [$hour, $minute] = explode(':', $data['start_time']);

                    $current = Carbon::parse($data['date_from']);
                    $until   = Carbon::parse($data['date_to']);
                    $prefix  = trim($data['name'] ?? Event::typeLabel((int) $data['type']));
                    $created = 0;

                    $createEvent = function (Carbon $date) use ($data, $collaId, $groupId, $hour, $minute, $prefix, &$created): void {
                        $startDate = $date->copy()->setTime((int) $hour, (int) $minute);
                        Event::create([
                            'colla_id'           => $collaId,
                            'name'               => $prefix . ' ' . $startDate->translatedFormat('d/m/Y'),
                            'type'               => (int) $data['type'],
                            'start_date'         => $startDate,
                            'address'            => $data['address'] ?? null,
                            'visibility'         => $data['visibility'],
                            'recurring_group_id' => $groupId,
                        ]);
                        $created++;
                    };

                    if ($data['repetition_type'] === 'weekly') {
                        $days = array_map('intval', $data['days_of_week']);
                        while ($current->lte($until)) {
                            if (in_array($current->dayOfWeek, $days)) {
                                $createEvent($current);
                            }
                            $current->addDay();
                        }
                    } elseif ($data['repetition_type'] === 'every_x') {
                        $interval = max(1, (int) $data['interval_days']);
                        while ($current->lte($until)) {
                            $createEvent($current);
                            $current->addDays($interval);
                        }
                    } elseif ($data['repetition_type'] === 'monthly') {
                        while ($current->lte($until)) {
                            $createEvent($current);
                            $current->addMonth();
                        }
                    }

                    Notification::make()
                        ->title("Creats {$created} esdeveniments")
                        ->success()
                        ->send();
                }),
        ];
    }
}
