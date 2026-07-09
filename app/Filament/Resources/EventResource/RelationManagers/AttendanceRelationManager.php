<?php

declare(strict_types=1);

namespace App\Filament\Resources\EventResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class AttendanceRelationManager extends RelationManager
{
    protected static string $relationship = 'attendance';

    protected static ?string $title = 'Assistència';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('casteller_id')
                ->label('Casteller')
                ->relationship('casteller', 'alias')
                ->searchable()
                ->required(),
            Select::make('status')
                ->label('Estat')
                ->options([
                    'ok' => 'Assisteix',
                    'nok' => 'No assisteix',
                    'unknown' => 'Pendent',
                ])
                ->default('unknown')
                ->required(),
            TextInput::make('companions')
                ->label('Acompanyants')
                ->numeric()
                ->default(0)
                ->minValue(0),
        ]);
    }

    public function table(Table $table): Table
    {
        $event = $this->getOwnerRecord();

        return $table
            ->columns([
                TextColumn::make('casteller.alias')
                    ->label('Casteller')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('casteller.name')
                    ->label('Nom')
                    ->formatStateUsing(fn ($record) => $record->casteller->full_name),
                TextColumn::make('status')
                    ->label('Estat')
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
                TextColumn::make('companions')
                    ->label('Acompanyants'),
            ])
            ->headerActions([
                Action::make('veure_respostes')
                    ->label('Veure respostes')
                    ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                    ->color('info')
                    ->modalHeading('Respostes als qüestionaris')
                    ->modalContent(function () use ($event): HtmlString {
                        $questions   = $event->questions()->orderBy('order')->get();
                        $attendances = $event->attendance()
                            ->with(['casteller', 'answers'])
                            ->orderBy('created_at')
                            ->get();

                        if ($questions->isEmpty()) {
                            return new HtmlString('<p class="text-sm text-gray-500 p-4">Aquest event no té preguntes.</p>');
                        }

                        if ($attendances->isEmpty()) {
                            return new HtmlString('<p class="text-sm text-gray-500 p-4">Sense respostes registrades.</p>');
                        }

                        $th = fn (string $label) => '<th class="text-left p-2 border-b font-semibold whitespace-nowrap">' . e($label) . '</th>';

                        $html  = '<div class="overflow-x-auto p-4">';
                        $html .= '<table class="w-full text-sm border-collapse">';
                        $html .= '<thead><tr>';
                        $html .= $th('Casteller') . $th('Assistència') . $th('Acompanyants');
                        foreach ($questions as $q) {
                            $html .= $th($q->text);
                        }
                        $html .= '</tr></thead><tbody>';

                        foreach ($attendances as $att) {
                            $html .= '<tr class="border-b hover:bg-gray-50">';
                            $html .= '<td class="p-2 font-medium">' . e($att->casteller->alias) . '</td>';
                            $html .= '<td class="p-2">' . match ($att->status) {
                                'ok'  => '<span class="text-green-600 font-medium">Sí</span>',
                                'nok' => '<span class="text-red-600 font-medium">No</span>',
                                default => '<span class="text-gray-400">Pendent</span>',
                            } . '</td>';
                            $html .= '<td class="p-2 text-center">' . e($att->companions) . '</td>';
                            foreach ($questions as $q) {
                                $answer = $att->answers->firstWhere('question_id', $q->id);
                                $value  = '';
                                if ($answer) {
                                    $value = is_array($answer->answer)
                                        ? implode(', ', $answer->answer)
                                        : (string) $answer->answer;
                                }
                                $html .= '<td class="p-2">' . e($value) . '</td>';
                            }
                            $html .= '</tr>';
                        }

                        $html .= '</tbody></table></div>';
                        return new HtmlString($html);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tancar')
                    ->modalWidth('5xl')
                    ->visible(fn (): bool => $event->questions()->exists()),

                Action::make('exportar_csv')
                    ->label('Descarregar CSV')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('gray')
                    ->url(fn (): string => url('/admin/' . $event->colla->shortname . '/events/' . $event->id . '/export-answers'))
                    ->openUrlInNewTab()
                    ->visible(fn (): bool => $event->questions()->exists()),
            ])
            ->defaultSort('casteller.alias');
    }
}
