<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
        return auth()->user()?->canDo('config') ?? false;
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
                    Toggle::make('show_logo_in_pinya')
                        ->label('Mostrar logo a les pinyes')
                        ->helperText('Si activat, el logo apareix a totes les plantilles i a la pissarra.')
                        ->default(false),
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

                Section::make('Protecció de dades (RGPD)')->schema([
                    TextInput::make('gdpr_version')
                        ->label('Versió del text RGPD')
                        ->default('1.0')
                        ->maxLength(20)
                        ->helperText('Incrementar la versió obliga tots els membres a acceptar de nou.'),
                    TextInput::make('gdpr_email')
                        ->label('Correu de contacte RGPD (opcional)')
                        ->email()
                        ->maxLength(255)
                        ->helperText('Si buida, s\'usa el correu general de la colla.'),
                    Textarea::make('gdpr_text')
                        ->label('Text de la política de privacitat')
                        ->rows(12)
                        ->columnSpanFull()
                        ->helperText('S\'usa {nom_colla}, {email_colla} i {email_rgpd} com a variables.'),
                ])->columns(2),
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
