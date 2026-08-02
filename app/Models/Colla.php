<?php

declare(strict_types=1);

namespace App\Models;

use Filament\Models\Contracts\HasAvatar;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Event;
use Illuminate\Support\Str;

class Colla extends Model implements HasAvatar
{
    use HasFactory;

    protected $table = 'colles';

    protected $fillable = [
        'name',
        'shortname',
        'email',
        'phone',
        'city',
        'country',
        'logo',
        'show_logo_in_pinya',
        'color',
        'max_members',
        'assaigs_weeks_visible',
        'gdpr_text',
        'gdpr_version',
        'gdpr_email',
        'display_token',
        'projected_snapshot_id',
        'rondes_display',
        'board_settings',
        'generator_config',
        'stripe_account_id',
        'stripe_onboarded_at',
    ];

    protected $casts = [
        'board_settings'     => 'array',
        'generator_config'   => 'array',
        'stripe_onboarded_at' => 'datetime',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function projectedSnapshot(): BelongsTo
    {
        return $this->belongsTo(BoardSnapshot::class, 'projected_snapshot_id');
    }

    public function ensureDisplayToken(): string
    {
        if (!$this->display_token) {
            $this->update(['display_token' => (string) Str::uuid()]);
        }
        return $this->display_token;
    }

    public function getDisplayUrl(): string
    {
        return route('display.projector', [
            'shortname' => $this->shortname,
            'token'     => $this->ensureDisplayToken(),
        ]);
    }

    public function castellers(): HasMany
    {
        return $this->hasMany(Casteller::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->logo ? secure_asset('storage/'.$this->logo) : null;
    }

    public function isStripeConnected(): bool
    {
        return filled($this->stripe_account_id) && $this->stripe_onboarded_at !== null;
    }

    public function resolveGdprText(): string
    {
        $contactEmail = $this->gdpr_email ?: ($this->email ?? '');

        return strtr($this->gdpr_text ?? '', [
            '{nom_colla}'   => $this->name,
            '{email_colla}' => $this->email ?? '',
            '{email_rgpd}'  => $contactEmail,
        ]);
    }

    public static function defaultGdprText(string $collaName, string $collaEmail): string
    {
        return <<<TEXT
        POLÍTICA DE PROTECCIÓ DE DADES PERSONALS

        Responsable del tractament: {$collaName}
        Correu de contacte: {$collaEmail}

        En compliment del Reglament (UE) 2016/679 (RGPD) i la Llei Orgànica 3/2018 (LOPDGDD), l'informem que les seves dades personals seran tractades per {$collaName} amb les finalitats següents:

        • Gestió de la participació en activitats castelleres (assajos, actuacions i esdeveniments).
        • Comunicació d'informació relacionada amb la colla.
        • Compliment d'obligacions legals, si escau.

        BASE JURÍDICA
        El tractament es basa en el seu consentiment explícit i en l'execució de la relació associativa.

        DRETS
        Podeu exercir els drets d'accés, rectificació, supressió, portabilitat, limitació i oposició adreçant-vos a {$collaEmail}.

        CONSERVACIÓ
        Les dades es conservaran mentre es mantingui la relació associativa i durant el temps necessari per a complir les obligacions legals.

        Acceptant aquesta política, doneu el vostre consentiment explícit al tractament de les vostres dades personals per les finalitats descrites.
        TEXT;
    }
}
