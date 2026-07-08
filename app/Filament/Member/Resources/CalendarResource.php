<?php

declare(strict_types=1);

namespace App\Filament\Member\Resources;

use App\Filament\Member\Resources\CalendarResource\Pages;
use App\Models\Attendance;
use App\Models\Event;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
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
            ->where(fn (Builder $q) => $q
                ->where(fn (Builder $inner) => $inner
                    ->where('type', 1)
                    ->where('start_date', '>=', now()->subDays(7))
                    ->where('start_date', '<=', now()->addWeeks(2))
                )
                ->orWhere(fn (Builder $inner) => $inner
                    ->whereIn('type', [2, 3])
                    ->where('start_date', '>=', now()->subDays(7))
                )
            )
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
                    ->label('Nom')
                    ->toggleable(),
                TextColumn::make('type')
                    ->label('Tipus')
                    ->badge()
                    ->formatStateUsing(fn (int $state) => Event::typeLabel($state))
                    ->color(fn (int $state) => match ($state) {
                        1 => 'info',
                        2 => 'success',
                        3 => 'warning',
                        default => 'gray',
                    })
                    ->toggleable(),
                TextColumn::make('address')
                    ->label('Lloc')
                    ->toggleable(),
                TextColumn::make('myStatus')
                    ->label('Assistència')
                    ->badge()
                    ->state(function (Event $record): string {
                        $attendance = $record->attendance()
                            ->where('casteller_id', auth('member')->id())
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
            ->filters([])
            ->actions([
                Action::make('confirmar')
                    ->label('Confirmar')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('primary')
                    ->form([
                        Radio::make('status')
                            ->label('La meva assistència')
                            ->options([
                                'ok'  => 'Assisteixo',
                                'nok' => 'No assisteixo',
                            ])
                            ->required(),
                    ])
                    ->fillForm(function (Event $record): array {
                        $attendance = $record->attendance()
                            ->where('casteller_id', auth('member')->id())
                            ->first();
                        return ['status' => $attendance?->status];
                    })
                    ->action(function (Event $record, array $data): void {
                        Attendance::updateOrCreate(
                            ['event_id' => $record->id, 'casteller_id' => auth('member')->id()],
                            ['status' => $data['status']]
                        );
                        $msg = $data['status'] === 'ok' ? 'Assisteixo ✓' : 'No assisteixo ✗';
                        Notification::make()->title($msg)->success()->send();
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
