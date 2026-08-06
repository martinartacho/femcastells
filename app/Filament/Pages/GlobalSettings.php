<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\GlobalSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class GlobalSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?string $navigationLabel = 'Configuració global';

    protected static ?string $title = 'Configuració global del sistema';

    protected static ?int $navigationSort = 100;

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super-admin') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'demo_enabled' => GlobalSetting::get('demo_enabled', '1') === '1',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Demo pública')
                    ->description('Controla l\'accés a la demo des de l\'app mòbil sense registre.')
                    ->schema([
                        Toggle::make('demo_enabled')
                            ->label('Demo activa')
                            ->helperText('Si desactivada, el botó "Prova la demo" retorna un error 503.')
                            ->onColor('success')
                            ->offColor('danger'),
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
        $data = $this->form->getState();
        GlobalSetting::set('demo_enabled', $data['demo_enabled'] ? '1' : '0');
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
