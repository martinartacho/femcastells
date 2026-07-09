<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\QuestionResource\Pages;
use App\Models\Question;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QuestionResource extends Resource
{
    protected static ?string $model = Question::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static ?string $navigationLabel = 'Preguntes';

    protected static \UnitEnum|string|null $navigationGroup = null;

    protected static ?int $navigationSort = 6;

    public static function canViewAny(): bool
    {
        return auth()->user()?->canDo('esdeveniments') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                TextInput::make('text')
                    ->label('Pregunta')
                    ->required()
                    ->maxLength(500)
                    ->columnSpanFull(),
                Select::make('type')
                    ->label('Tipus')
                    ->options([
                        'radio'    => 'Opció única (radio)',
                        'checkbox' => 'Opció múltiple (checkbox)',
                        'text'     => 'Text lliure',
                    ])
                    ->required()
                    ->native(false)
                    ->live(),
                Repeater::make('options')
                    ->label('Opcions de resposta')
                    ->schema([
                        TextInput::make('value')->label('Opció')->required()->maxLength(200),
                    ])
                    ->addActionLabel('Afegir opció')
                    ->minItems(2)
                    ->visible(fn ($get) => in_array($get('type'), ['radio', 'checkbox']))
                    ->required(fn ($get) => in_array($get('type'), ['radio', 'checkbox']))
                    ->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('text')
                    ->label('Pregunta')
                    ->limit(60)
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Tipus')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Question::typeLabel($state))
                    ->color(fn (string $state) => match ($state) {
                        'radio'    => 'info',
                        'checkbox' => 'warning',
                        'text'     => 'gray',
                        default    => 'gray',
                    }),
                TextColumn::make('events_count')
                    ->label('Events')
                    ->counts('events')
                    ->sortable(),
            ])
            ->defaultSort('text');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListQuestions::route('/'),
            'create' => Pages\CreateQuestion::route('/create'),
            'edit'   => Pages\EditQuestion::route('/{record}/edit'),
        ];
    }
}
