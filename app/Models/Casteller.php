<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Casteller extends Model
{
    protected $fillable = [
        'colla_id',
        'alias',
        'name',
        'last_name',
        'email',
        'phone',
        'birthdate',
        'status',
        'height',
        'weight',
        'shoulder_height',
        'photo',
        'comments',
    ];

    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
            'status' => 'integer',
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
