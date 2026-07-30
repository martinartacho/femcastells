<?php

declare(strict_types=1);

namespace App\Filament\Resources\CastellerResource\Pages;

use App\Filament\Resources\CastellerResource;
use App\Models\Casteller;
use App\Models\Tag;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Table;

class ListCastellerProfiles extends ListRecords
{
    protected static string $resource = CastellerResource::class;

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Perfils físics';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('alias')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Nom')
                    ->formatStateUsing(fn ($record) => $record->full_name)
                    ->searchable(),
                TextInputColumn::make('weight')
                    ->label('Pes (kg)')
                    ->type('number')
                    ->rules(['nullable', 'integer', 'min:30', 'max:200'])
                    ->extraInputAttributes(['style' => 'width:70px']),
                TextInputColumn::make('height')
                    ->label('Alçada (cm)')
                    ->type('number')
                    ->rules(['nullable', 'integer', 'min:100', 'max:250'])
                    ->extraInputAttributes(['style' => 'width:70px']),
                TextInputColumn::make('shoulder_height')
                    ->label('Espatlla (cm)')
                    ->type('number')
                    ->rules(['nullable', 'integer', 'min:80', 'max:220'])
                    ->extraInputAttributes(['style' => 'width:70px']),
                TextColumn::make('position_tags')
                    ->label('Posició')
                    ->getStateUsing(fn (Casteller $record): string =>
                        $record->tags->where('group', 'Posicio')->pluck('name')->join(', ') ?: '—'
                    )
                    ->badge()
                    ->color('info'),
            ])
            ->actions([
                Action::make('edit_posicio')
                    ->label('')
                    ->icon(Heroicon::OutlinedTag)
                    ->tooltip('Editar posició')
                    ->color('gray')
                    ->fillForm(fn (Casteller $record): array => [
                        'tag_ids' => $record->tags()
                            ->where('group', 'Posicio')
                            ->pluck('tags.id')
                            ->toArray(),
                    ])
                    ->form(fn (): array => [
                        CheckboxList::make('tag_ids')
                            ->label('Posicions habituals')
                            ->options(
                                Tag::where('colla_id', auth()->user()->colla_id)
                                    ->where('group', 'Posicio')
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                            )
                            ->columns(2),
                    ])
                    ->action(function (Casteller $record, array $data): void {
                        $posicioTagIds = Tag::where('colla_id', auth()->user()->colla_id)
                            ->where('group', 'Posicio')
                            ->pluck('id')
                            ->toArray();

                        // Elimina les posicio actuals i assigna les noves sense tocar altres tags
                        $record->tags()->detach($posicioTagIds);
                        if (!empty($data['tag_ids'])) {
                            $record->tags()->attach($data['tag_ids']);
                        }
                    })
                    ->modalHeading(fn (Casteller $record): string => 'Posició — ' . $record->alias)
                    ->modalWidth('sm')
                    ->modalSubmitActionLabel('Desa'),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('Estat')
                    ->options([1 => 'Actiu', 2 => 'Inactiu', 3 => 'Baixa', 4 => 'Candidat'])
                    ->default(1),
            ])
            ->defaultSort('alias')
            ->recordUrl(null)
            ->modifyQueryUsing(fn ($query) => $query->with('tags'));
    }
}
