<?php

declare(strict_types=1);

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

class Casteller extends Authenticatable implements FilamentUser, HasTenants, CanResetPasswordContract
{
    use Notifiable, CanResetPassword;

    protected $fillable = [
        'colla_id',
        'alias',
        'name',
        'last_name',
        'email',
        'email2',
        'phone',
        'mobile_phone',
        'emergency_phone',
        'address',
        'postal_code',
        'city',
        'province',
        'country',
        'birthdate',
        'subscription_date',
        'gender',
        'nationality',
        'national_id',
        'national_id_type',
        'num_soci',
        'family',
        'family_head',
        'status',
        'height',
        'weight',
        'shoulder_height',
        'photo',
        'comments',
        'password',
        'gdpr_accepted_at',
        'gdpr_version',
        'last_login_at',
        'login_count',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
            'status' => 'integer',
            'password' => 'hashed',
            'noticies_last_seen_at' => 'datetime',
            'gdpr_accepted_at' => 'datetime',
            'last_login_at'    => 'datetime',
            'login_count'      => 'integer',
        ];
    }

    public function colla(): BelongsTo
    {
        return $this->belongsTo(Colla::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function getTenants(Panel $panel): Collection
    {
        return $this->colla ? collect([$this->colla]) : collect();
    }

    public function getDefaultTenant(Panel $panel): ?\Illuminate\Database\Eloquent\Model
    {
        return $this->colla;
    }

    public function canAccessTenant(\Illuminate\Database\Eloquent\Model $tenant): bool
    {
        return $this->colla_id === $tenant->id;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'member' && $this->colla_id !== null;
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->name} {$this->last_name}");
    }

    public static function statusLabel(int $status): string
    {
        return match ($status) {
            1 => 'Actiu',
            2 => 'Inactiu',
            3 => 'Baixa',
            4 => 'Candidat',
            default => 'Desconegut',
        };
    }
}
