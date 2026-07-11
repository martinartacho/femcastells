<?php

declare(strict_types=1);

namespace App\Filament\Member\Pages;

use App\Models\Casteller;
use App\Models\Colla;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class EditProfile extends \Filament\Auth\Pages\EditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identificació')->schema([
                TextInput::make('alias')
                    ->label('Àlies')
                    ->disabled(),
                TextInput::make('name')
                    ->label('Nom')
                    ->disabled(),
                TextInput::make('last_name')
                    ->label('Cognoms')
                    ->disabled(),
                DatePicker::make('birthdate')
                    ->label('Data de naixement')
                    ->displayFormat('d/m/Y')
                    ->disabled(),
            ])->columns(2),

            Section::make('Contacte')->schema([
                TextInput::make('email')
                    ->label('Correu electrònic')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('email2')
                    ->label('Correu alternatiu')
                    ->email(),
                TextInput::make('phone')
                    ->label('Telèfon')
                    ->tel(),
                TextInput::make('mobile_phone')
                    ->label('Mòbil')
                    ->tel(),
                TextInput::make('emergency_phone')
                    ->label('Telèfon d\'emergència')
                    ->tel(),
            ])->columns(2),

            Section::make('Adreça')->schema([
                TextInput::make('address')
                    ->label('Adreça')
                    ->columnSpanFull(),
                TextInput::make('postal_code')
                    ->label('Codi postal')
                    ->maxLength(10),
                TextInput::make('city')
                    ->label('Població'),
                TextInput::make('province')
                    ->label('Província'),
                TextInput::make('country')
                    ->label('País'),
            ])->columns(2),

            Section::make('Canviar contrasenya')->schema([
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ])->columns(2),

            $this->getGdprSection(),
        ]);
    }

    private function getGdprSection(): Section
    {
        /** @var Casteller $casteller */
        $casteller = $this->getUser();
        $colla     = $casteller->colla;

        $acceptedAt = $casteller->gdpr_accepted_at;
        $version    = $casteller->gdpr_version;

        if ($acceptedAt) {
            $statusHtml = '<span style="display:inline-flex;align-items:center;gap:6px;font-size:.875rem;font-weight:500;color:#15803d">'
                . '<svg style="width:1rem;height:1rem;flex-shrink:0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>'
                . 'Acceptat'
                . '</span>';
        } else {
            $statusHtml = '<span style="display:inline-flex;align-items:center;gap:6px;font-size:.875rem;font-weight:500;color:#b45309">'
                . '<svg style="width:1rem;height:1rem;flex-shrink:0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>'
                . 'Pendent d\'acceptació'
                . '</span>';
        }

        return Section::make('Protecció de dades')->schema([
            Placeholder::make('gdpr_status')
                ->label('Estat del consentiment')
                ->content(new HtmlString($statusHtml)),
            Placeholder::make('gdpr_accepted_version')
                ->label('Versió acceptada')
                ->content($acceptedAt ? ($version ?? '—') : '—'),
            Placeholder::make('gdpr_accepted_at')
                ->label('Data d\'acceptació')
                ->content($acceptedAt ? $acceptedAt->format('d/m/Y H:i') : '—'),
            Placeholder::make('gdpr_colla_version')
                ->label('Versió vigent de la colla')
                ->content($colla?->gdpr_version ?? '—'),
        ])->columns(2);
    }

    protected function getHeaderActions(): array
    {
        /** @var Casteller $casteller */
        $casteller = $this->getUser();
        $colla     = $casteller->colla;

        if (! $colla?->gdpr_text) {
            return [];
        }

        $policyHtml = '<div class="prose dark:prose-invert max-w-none text-sm leading-relaxed">'
            . nl2br(e($colla->resolveGdprText()))
            . '</div>';

        return [
            Action::make('view_policy')
                ->label('Política de privacitat')
                ->icon(Heroicon::OutlinedDocumentText)
                ->color('gray')
                ->modalHeading('Política de privacitat — ' . $colla->name)
                ->modalContent(new HtmlString($policyHtml))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tancar'),

            Action::make('withdraw_consent')
                ->label('Retirar consentiment')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Retirar el consentiment de protecció de dades')
                ->modalDescription(new HtmlString(
                    '<p class="text-sm text-gray-600 dark:text-gray-400">Si retireu el consentiment:</p>'
                    . '<ul class="mt-2 space-y-1 text-sm text-gray-600 dark:text-gray-400 list-disc list-inside">'
                    . '<li>Perdreu l\'accés al portal de membres fins que el torneu a acceptar.</li>'
                    . '<li>No rebreu comunicats ni convocatòries d\'aquesta colla.</li>'
                    . '<li>Les vostres dades personals es conservaran als sistemes de la colla d\'acord amb les seves obligacions legals.</li>'
                    . '</ul>'
                    . '<p class="mt-3 text-sm font-medium text-red-600 dark:text-red-400">Voleu continuar?</p>'
                ))
                ->modalSubmitActionLabel('Sí, retiro el consentiment')
                ->action(fn () => $this->doWithdrawConsent()),
        ];
    }

    public function doWithdrawConsent(): void
    {
        /** @var Casteller $casteller */
        $casteller = $this->getUser();

        $casteller->update([
            'gdpr_accepted_at' => null,
            'gdpr_version'     => null,
        ]);

        auth('member')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        $this->redirect(filament()->getLoginUrl());
    }
}
