<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Colla;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
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
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Stripe\OAuth;
use Stripe\Stripe;

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

    public function disconnectStripe(): void
    {
        $colla = filament()->getTenant();

        Stripe::setApiKey(config('services.stripe.secret'));
        OAuth::deauthorize([
            'client_id' => config('services.stripe.connect_client_id'),
            'stripe_user_id' => $colla->stripe_account_id,
        ]);

        $colla->update([
            'stripe_account_id' => null,
            'stripe_onboarded_at' => null,
        ]);

        Notification::make()->title('Stripe desconnectat')->success()->send();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('settings')
                    ->tabs([
                        Tab::make('Identitat visual')
                            ->icon(Heroicon::OutlinedPhoto)
                            ->schema([
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

                        Tab::make('Membres')
                            ->icon(Heroicon::OutlinedUsers)
                            ->schema([
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

                        Tab::make('Pissarra')
                            ->icon(Heroicon::OutlinedTableCells)
                            ->schema([
                                TextInput::make('board_settings.height_alert_cm')
                                    ->label('Llindar alçada espatlla (cm)')
                                    ->helperText('Si la diferència entre castellers supera aquest valor, apareix ⚠ al costat de la posició.')
                                    ->numeric()
                                    ->minValue(5)
                                    ->maxValue(50)
                                    ->step(1)
                                    ->default(15)
                                    ->suffix('cm'),
                            ]),

                        Tab::make('Generador')
                            ->icon(Heroicon::OutlinedCalculator)
                            ->schema([
                                Section::make('Generador de pinyes — pesos per categoria')
                                    ->description('Percentatges que determinen com es puntua cada casteller per a cada tipus de posició. La suma de pes + alçada + espatlla no cal que sigui 100.')
                                    ->schema([
                                        Section::make('Baix / Base')->schema([
                                            TextInput::make('generator_config.baix.weight')
                                                ->label('% Pes')->numeric()->default(60)->suffix('%')->minValue(0)->maxValue(100),
                                            TextInput::make('generator_config.baix.shoulder')
                                                ->label('% Espatlla')->numeric()->default(40)->suffix('%')->minValue(0)->maxValue(100),
                                            TextInput::make('generator_config.baix.height')
                                                ->label('% Alçada')->numeric()->default(0)->suffix('%')->minValue(0)->maxValue(100),
                                        ])->columns(3)->compact(),

                                        Section::make('Primer anell (Rengle, Falca, Angle, Lateral)')->schema([
                                            TextInput::make('generator_config.rengle.weight')
                                                ->label('% Pes')->numeric()->default(50)->suffix('%')->minValue(0)->maxValue(100),
                                            TextInput::make('generator_config.rengle.shoulder')
                                                ->label('% Espatlla')->numeric()->default(20)->suffix('%')->minValue(0)->maxValue(100),
                                            TextInput::make('generator_config.rengle.height')
                                                ->label('% Alçada')->numeric()->default(30)->suffix('%')->minValue(0)->maxValue(100),
                                        ])->columns(3)->compact(),

                                        Section::make('Nucli / Pisos alts (Nucli, Tronc, Contrafort, Folre…)')->schema([
                                            TextInput::make('generator_config.nucli.weight')
                                                ->label('% Pes (negatiu = menys pes és millor)')->numeric()->default(-50)->suffix('%')->minValue(-200)->maxValue(0),
                                            TextInput::make('generator_config.nucli.height')
                                                ->label('% Alçada')->numeric()->default(20)->suffix('%')->minValue(0)->maxValue(100),
                                            TextInput::make('generator_config.nucli.base')
                                                ->label('Puntuació base')->numeric()->default(150)->minValue(0)->maxValue(500),
                                        ])->columns(3)->compact(),

                                        Section::make('Agulla (cim del castell)')->schema([
                                            TextInput::make('generator_config.agulla.weight')
                                                ->label('% Pes (negatiu = menys pes és millor)')->numeric()->default(-200)->suffix('%')->minValue(-500)->maxValue(0),
                                            TextInput::make('generator_config.agulla.base')
                                                ->label('Puntuació base')->numeric()->default(200)->minValue(0)->maxValue(500),
                                        ])->columns(2)->compact(),
                                    ]),
                            ]),

                        Tab::make('Botiga')
                            ->icon(Heroicon::OutlinedShoppingBag)
                            ->schema([
                                Select::make('shop_visibility')
                                    ->label('Visibilitat de la botiga')
                                    ->options(Colla::SHOP_VISIBILITY_OPTIONS)
                                    ->default(Colla::SHOP_DISABLED)
                                    ->native(false)
                                    ->required()
                                    ->helperText('"Només membres" requereix login al portal membre. "Pública" permet comprar sense compte.'),
                            ]),

                        Tab::make('Pagament')
                            ->icon(Heroicon::OutlinedBanknotes)
                            ->schema([
                                Placeholder::make('stripe_status')
                                    ->label('Estat de connexió amb Stripe')
                                    ->content(function () {
                                        $colla = filament()->getTenant();

                                        if (!$colla?->isStripeConnected()) {
                                            return 'No connectat.';
                                        }

                                        return sprintf(
                                            'Connectat ✓ — compte %s (des del %s)',
                                            $colla->stripe_account_id,
                                            $colla->stripe_onboarded_at->format('d/m/Y H:i'),
                                        );
                                    }),

                                Actions::make([
                                    Action::make('stripeConnect')
                                        ->label(fn () => filament()->getTenant()?->isStripeConnected()
                                            ? 'Reconnectar amb Stripe'
                                            : 'Connectar amb Stripe')
                                        ->icon(Heroicon::OutlinedBanknotes)
                                        ->color('primary')
                                        ->url(fn () => route('stripe.connect', ['shortname' => filament()->getTenant()->shortname])),

                                    Action::make('stripeDisconnect')
                                        ->label('Desconnectar Stripe')
                                        ->icon(Heroicon::OutlinedXCircle)
                                        ->color('danger')
                                        ->requiresConfirmation()
                                        ->modalDescription('Es revocarà l\'accés del compte Stripe d\'aquesta colla. Caldrà tornar a autoritzar per rebre pagaments.')
                                        ->action('disconnectStripe')
                                        ->visible(fn () => filament()->getTenant()?->isStripeConnected() ?? false),
                                ]),
                            ]),

                        Tab::make('RGPD')
                            ->icon(Heroicon::OutlinedShieldCheck)
                            ->schema([
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
