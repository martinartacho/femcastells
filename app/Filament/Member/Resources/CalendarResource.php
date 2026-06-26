<?php

declare(strict_types=1);

namespace App\Filament\Member\Resources;

use App\Filament\Member\Resources\CalendarResource\Pages;
use App\Models\Attendance;
use App\Models\Event;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CalendarResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Calendari';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('visibility', true)
            ->where('start_date', '>=', now()->subDays(7))
            ->orderBy('start_date');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('start_date')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nom'),
                TextColumn::make('type')
                    ->label('Tipus')
                    ->badge()
                    ->formatStateUsing(fn (int $state) => Event::typeLabel($state))
                    ->color(fn (int $state) => match ($state) {
                        1 => 'info',
                        2 => 'success',
                        3 => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('address')
                    ->label('Lloc')
                    ->toggleable(),
                TextColumn::make('myStatus')
                    ->label('La meva assistència')
                    ->badge()
                    ->state(function (Event $record): string {
                        $attendance = $record->attendance()
                            ->where('casteller_id', auth()->id())
                            ->first();
                        return $attendance?->status ?? 'unknown';
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'ok' => 'Assisteixo',
                        'nok' => 'No assisteixo',
                        default => 'Pendent',
                    })
                    ->color(fn (string $state) => match ($state) {
                        'ok' => 'success',
                        'nok' => 'danger',
                        default => 'warning',
                    }),
            ])
            ->actions([
                Action::make('attend')
                    ->label('Assisteixo')
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('success')
                    ->action(function (Event $record): void {
                        Attendance::updateOrCreate(
                            ['event_id' => $record->id, 'casteller_id' => auth()->id()],
                            ['status' => 'ok']
                        );
                        Notification::make()->title('Confirmat: assisteixo')->success()->send();
                    })
                    ->visible(fn (Event $record): bool => $record->close_date === null || $record->close_date->isFuture()),
                Action::make('absent')
                    ->label('No assisteixo')
                    ->icon(Heroicon::OutlinedXMark)
                    ->color('danger')
                    ->action(function (Event $record): void {
                        Attendance::updateOrCreate(
                            ['event_id' => $record->id, 'casteller_id' => auth()->id()],
                            ['status' => 'nok']
                        );
                        Notification::make()->title('Confirmat: no assisteixo')->warning()->send();
                    })
                    ->visible(fn (Event $record): bool => $record->close_date === null || $record->close_date->isFuture()),
            ])
            ->paginated(false);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCalendar::route('/'),
        ];
    }
}
