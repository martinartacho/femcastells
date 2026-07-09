<?php

declare(strict_types=1);

namespace App\Filament\Member\Resources;

use App\Filament\Member\Resources\CalendarResource\Pages;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\EventAnswer;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
        $weeks = filament()->getTenant()?->assaigs_weeks_visible ?? 2;

        return parent::getEloquentQuery()
            ->where('visibility', true)
            ->where(fn (Builder $q) => $q
                ->where(fn (Builder $inner) => $inner
                    ->where('type', 1)
                    ->where('start_date', '>=', now()->subDays(7))
                    ->where('start_date', '<=', now()->addWeeks($weeks))
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
                    ->form(function (Event $record): array {
                        $fields = [
                            Radio::make('status')
                                ->label('La meva assistència')
                                ->options(['ok' => 'Assisteixo', 'nok' => 'No assisteixo'])
                                ->required(),
                        ];

                        if ($record->companions) {
                            $fields[] = TextInput::make('companions')
                                ->label('Nombre d\'acompanyants')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(20)
                                ->default(0);
                        }

                        foreach ($record->questions()->get() as $question) {
                            $field = match ($question->type) {
                                'radio' => Radio::make("q_{$question->id}")
                                    ->label($question->text)
                                    ->options(collect($question->options)->pluck('value', 'value')->all())
                                    ->required((bool) $question->pivot->required),
                                'checkbox' => CheckboxList::make("q_{$question->id}")
                                    ->label($question->text)
                                    ->options(collect($question->options)->pluck('value', 'value')->all())
                                    ->required((bool) $question->pivot->required),
                                'text' => Textarea::make("q_{$question->id}")
                                    ->label($question->text)
                                    ->rows(2)
                                    ->required((bool) $question->pivot->required),
                                default => null,
                            };
                            if ($field) {
                                $fields[] = $field;
                            }
                        }

                        return $fields;
                    })
                    ->fillForm(function (Event $record): array {
                        $attendance = $record->attendance()
                            ->where('casteller_id', auth('member')->id())
                            ->with('answers')
                            ->first();

                        $data = [
                            'status' => $attendance?->status,
                            'companions' => $attendance?->companions ?? 0,
                        ];

                        $questionTypes = $record->questions()->pluck('type', 'questions.id');

                        foreach ($attendance?->answers ?? [] as $answer) {
                            $value = $answer->answer;
                            $type = $questionTypes->get($answer->question_id);
                            if ($type !== 'checkbox' && is_array($value)) {
                                $value = $value[0] ?? null;
                            }
                            $data["q_{$answer->question_id}"] = $value;
                        }

                        return $data;
                    })
                    ->action(function (Event $record, array $data): void {
                        $attendance = Attendance::updateOrCreate(
                            ['event_id' => $record->id, 'casteller_id' => auth('member')->id()],
                            [
                                'status' => $data['status'],
                                'companions' => $record->companions ? ($data['companions'] ?? 0) : 0,
                            ]
                        );

                        foreach ($record->questions()->get() as $question) {
                            $key = "q_{$question->id}";
                            if (array_key_exists($key, $data)) {
                                EventAnswer::updateOrCreate(
                                    ['attendance_id' => $attendance->id, 'question_id' => $question->id],
                                    ['answer' => $question->type === 'checkbox'
                                            ? (is_array($data[$key]) ? $data[$key] : [$data[$key]])
                                            : $data[$key]]
                                );
                            }
                        }

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
