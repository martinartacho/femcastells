<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\EventResource\Pages;
use App\Filament\Resources\EventResource\RelationManagers\AttendanceRelationManager;
use App\Models\Event;
use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Esdeveniments';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informació')->schema([
                TextInput::make('name')
                    ->label('Nom')
                    ->required()
                    ->maxLength(200),
                Select::make('type')
                    ->label('Tipus')
                    ->options([
                        1 => 'Assaig',
                        2 => 'Actuació',
                        3 => 'Activitat',
                    ])
                    ->default(1)
                    ->required(),
                TextInput::make('address')
                    ->label('Adreça')
                    ->maxLength(255),
                TextInput::make('duration')
                    ->label('Durada (min)')
                    ->numeric()
                    ->minValue(0),
            ])->columns(2),

            Section::make('Dates')->schema([
                DateTimePicker::make('start_date')
                    ->label('Inici')
                    ->required()
                    ->displayFormat('d/m/Y H:i')
                    ->default(now()->setTime(14, 0))
                    ->seconds(false),
                DateTimePicker::make('open_date')
                    ->label('Obertura assistència')
                    ->displayFormat('d/m/Y H:i')
                    ->default(now()->startOfDay())
                    ->seconds(false),
                DateTimePicker::make('close_date')
                    ->label('Tancament assistència')
                    ->displayFormat('d/m/Y H:i')
                    ->default(now()->startOfDay())
                    ->seconds(false),
            ])->columns(3),

            Section::make('Opcions')->schema([
                Toggle::make('visibility')
                    ->label('Visible als membres')
                    ->default(true),
                Toggle::make('companions')
                    ->label('Permet acompanyants')
                    ->default(false),
            ])->columns(2),
        ]);
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
                    ->searchable(),
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
                TextColumn::make('attendance_count')
                    ->label('Assistència')
                    ->counts('attendance')
                    ->sortable(),
                IconColumn::make('visibility')
                    ->label('Visible')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipus')
                    ->options([
                        1 => 'Assaig',
                        2 => 'Actuació',
                        3 => 'Activitat',
                    ]),
            ])
            ->actions([
                Action::make('pissarra')
                    ->label('Pissarra')
                    ->icon(Heroicon::OutlinedTableCells)
                    ->color('info')
                    ->url(fn (Event $record): string => '/admin/' . auth()->user()->colla->shortname . '/pissarra?selectedEventId=' . $record->id),
            ])
            ->bulkActions([
                \Filament\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('start_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            AttendanceRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }
}
