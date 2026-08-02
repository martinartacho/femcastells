<?php

declare(strict_types=1);

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use App\Jobs\IssueIdcCredentialJob;
use App\Models\Attendance;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;

class ManageEventAttendance extends ManageRelatedRecords
{
    protected static string $resource = EventResource::class;

    protected static string $relationship = 'attendance';

    protected static ?string $navigationLabel = 'Assistència';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    public function getTitle(): string|Htmlable
    {
        return 'Assistència — '.$this->getRecord()->name;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('casteller.alias')
                    ->label('Casteller')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('casteller.name')
                    ->label('Nom')
                    ->formatStateUsing(fn ($record) => $record->casteller->full_name)
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Prevista')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'ok' => 'Assisteix',
                        'nok' => 'No assisteix',
                        'unknown' => 'Pendent',
                    })
                    ->color(fn (string $state) => match ($state) {
                        'ok' => 'success',
                        'nok' => 'danger',
                        'unknown' => 'warning',
                    }),
                ToggleColumn::make('checked_in')
                    ->label('Verificada')
                    ->onColor('success')
                    ->offColor('gray')
                    ->afterStateUpdated(function (Attendance $record, $state) {
                        $record->update([
                            'checked_in' => $state,
                            'checked_in_at' => $state ? now() : null,
                        ]);

                        if ($state && $record->event->type === 2) {
                            IssueIdcCredentialJob::dispatch($record->id);
                        }
                    }),
                TextColumn::make('checked_in_at')
                    ->label('Hora check-in')
                    ->time('H:i')
                    ->placeholder('—')
                    ->color('success'),
                TextColumn::make('companions')
                    ->label('Acompanyants'),
            ])
            ->defaultSort('casteller.alias')
            ->filters([
                SelectFilter::make('status')
                    ->label('Prevista')
                    ->options([
                        'ok' => 'Assisteix',
                        'nok' => 'No assisteix',
                        'unknown' => 'Pendent',
                    ]),
            ]);
    }
}
