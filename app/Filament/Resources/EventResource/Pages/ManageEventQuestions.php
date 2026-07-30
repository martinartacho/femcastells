<?php

declare(strict_types=1);

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use App\Models\Question;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ManageEventQuestions extends ManageRelatedRecords
{
    protected static string $resource = EventResource::class;

    protected static string $relationship = 'questions';

    protected static ?string $navigationLabel = 'Preguntes';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Preguntes — ' . $this->getRecord()->name;
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('order')
            ->columns([
                TextColumn::make('text')
                    ->label('Pregunta')
                    ->limit(60),
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
