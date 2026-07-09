<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CollaSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Configuració';

    protected static ?string $title = 'Configuració de la colla';

    protected static ?int $navigationSort = 99;

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(['super-admin', 'admin']) ?? false;
    }

    public function mount(): void
    {
        $colla = filament()->getTenant();
        $this->form->fill($colla?->toArray() ?? []);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Identitat visual')->schema([
                    FileUpload::make('logo')
                        ->label('Logo de la colla')
                        ->image()
                        ->disk('public')
                        ->directory('logos')
                        ->maxSize(2048)
                        ->imagePreviewHeight('80')
                        ->getUploadedFileNameForStorageUsing(
                            fn (TemporaryUploadedFile $file) => 'colla-' . filament()->getTenant()?->shortname . '.' . $file->getClientOriginalExtension()
                        ),
                ])->columns(1),

                Section::make('Configuració de membres')->schema([
                    Select::make('assaigs_weeks_visible')
                        ->label('Assaigs visibles al portal membre')
                        ->options([
                            1 => '1 setmana',
                            2 => '2 setmanes',
                            3 => '3 setmanes',
                            4 => '4 setmanes (1 mes)',
                            8 => '8 setmanes (2 mesos)',
                        ])
                        ->default(2)
                        ->native(false)
                        ->required(),
                ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make($this->getFormActions()),
                ]),
        ]);
    }

    public function save(): void
    {
        $colla = filament()->getTenant();
        $data  = $this->form->getState();

        $colla->update($data);

        Notification::make()->title('Configuració desada')->success()->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Desar')
                ->submit('save'),
        ];
    }
}
