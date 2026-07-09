<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'colla_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasTenants
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    public function colla(): BelongsTo
    {
        return $this->belongsTo(Colla::class);
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function canDo(string $area, string $level = 'read'): bool
    {
        if ($this->hasRole(['super-admin', 'admin'])) {
            return true;
        }
        return $this->profile?->can($area, $level) ?? false;
    }

    public function getTenants(Panel $panel): Collection
    {
        if ($this->hasRole('super-admin')) {
            return Colla::all();
        }
        return $this->colla ? collect([$this->colla]) : collect();
    }

    public function getDefaultTenant(Panel $panel): ?Model
    {
        return $this->colla;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        if ($this->hasRole('super-admin')) {
            return true;
        }
        return $this->colla_id === $tenant->id;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'noticies_last_seen_at' => 'datetime',
        ];
    }
}
