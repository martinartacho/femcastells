<?php

declare(strict_types=1);

namespace App\Filament\Resources\EventResource\RelationManagers;

use App\Models\Question;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    protected static ?string $title = 'Preguntes';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('order')
            ->columns([
                TextColumn::make('text')
                    ->label('Pregunta')
                    ->limit(50),
                TextColumn::make('type')
                    ->label('Tipus')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Question::typeLabel($state)),
                IconColumn::make('pivot.required')
                    ->label('Obligatòria')
                    ->boolean(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Afegir pregunta')
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['text'])
                    ->recordTitle(fn ($record) => $record->text)
                    ->recordSelectOptionsQuery(fn ($query) => $query->where('colla_id', filament()->getTenant()->id))
                    ->form(fn (AttachAction $action): array => [
                        $action->getRecordSelect()->label('Pregunta'),
                        Toggle::make('required')->label('Obligatòria')->default(false),
                    ]),
            ])
            ->actions([
                DetachAction::make()->label('Treure'),
            ])
            ->bulkActions([
                DetachBulkAction::make(),
            ]);
    }
}
