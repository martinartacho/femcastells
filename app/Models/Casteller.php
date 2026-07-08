<?php

declare(strict_types=1);

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

class Casteller extends Authenticatable implements FilamentUser, HasTenants
{
    use Notifiable;

    protected $fillable = [
        'colla_id',
        'alias',
        'name',
        'last_name',
        'email',
        'password',
        'phone',
        'birthdate',
        'status',
        'height',
        'weight',
        'shoulder_height',
        'photo',
        'comments',
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
