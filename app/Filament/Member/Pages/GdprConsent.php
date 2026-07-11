<?php

declare(strict_types=1);

namespace App\Filament\Member\Pages;

use App\Models\Casteller;
use App\Models\Colla;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class GdprConsent extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $navigationLabel = 'Protecció de dades';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Protecció de dades personals';

    public bool $accepted = false;

    public function getColla(): ?Colla
    {
        return filament()->getTenant() ?? auth('member')->user()?->colla;
    }

    private function isVersionUpdate(): bool
    {
        /** @var Casteller|null $casteller */
        $casteller = auth('member')->user();
        $colla     = $this->getColla();

        return $casteller?->gdpr_accepted_at !== null
            && $casteller->gdpr_version !== ($colla?->gdpr_version ?? '1.0');
    }

    public function form(Schema $schema): Schema
    {
        $colla    = $this->getColla();
        $rawText  = $colla ? $colla->resolveGdprText() : '';
        $gdprHtml = nl2br(e($rawText));

        $components = [];

        if ($this->isVersionUpdate()) {
            $components[] = Placeholder::make('version_notice')
                ->hiddenLabel()
                ->content(new HtmlString(
                    '<div class="rounded-lg border border-amber-300 bg-amber-50 dark:bg-amber-950/30 dark:border-amber-700 px-4 py-3 text-sm text-amber-800 dark:text-amber-300">'
                    . '<strong>S\'han actualitzat les condicions de privacitat.</strong> '
                    . 'Reviseu el text actualitzat i torneu a acceptar per continuar.'
                    . '</div>'
                ))
                ->columnSpanFull();
        }

        $components[] = Placeholder::make('gdpr_body')
            ->hiddenLabel()
            ->content(new HtmlString(
                '<div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5 prose dark:prose-invert max-w-none text-sm leading-relaxed">'
                . $gdprHtml
                . '</div>'
            ))
            ->columnSpanFull();

        $components[] = Checkbox::make('accepted')
            ->label('He llegit i accepto la política de protecció de dades')
            ->required()
            ->accepted()
            ->columnSpanFull();

        return $schema->components($components);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('accept')
                ->footer([
                    Actions::make($this->getFormActions()),
                ]),
        ]);
    }

    public function accept(): void
    {
        $this->form->validate();

        $casteller = auth('member')->user();
        $colla     = $this->getColla();

        $casteller->update([
            'gdpr_accepted_at' => now(),
            'gdpr_version'     => $colla?->gdpr_version ?? '1.0',
        ]);

        Notification::make()->title('Gràcies! Dades acceptades.')->success()->send();

        $this->redirect(filament()->getUrl($colla));
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('accept')
                ->label('Acceptar i continuar')
                ->submit('accept'),
        ];
    }
}
