<?php

declare(strict_types=1);

namespace App\Models;

use Filament\Models\Contracts\HasAvatar;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Colla extends Model implements HasAvatar
{
    protected $table = 'colles';

    protected $fillable = [
        'name',
        'shortname',
        'email',
        'phone',
        'city',
        'country',
        'logo',
        'color',
        'max_members',
        'assaigs_weeks_visible',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function castellers(): HasMany
    {
        return $this->hasMany(Casteller::class);
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->logo ? asset('storage/'.$this->logo) : null;
    }
}
