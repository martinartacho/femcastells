<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    protected $fillable = ['colla_id', 'text', 'type', 'options'];

    protected function casts(): array
    {
        return [
            'options' => 'array',
        ];
    }

    public function colla(): BelongsTo
    {
        return $this->belongsTo(Colla::class);
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class)
            ->withPivot(['required', 'order'])
            ->orderByPivot('order');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(EventAnswer::class);
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            'radio'    => 'Opció única',
            'checkbox' => 'Opció múltiple',
            'text'     => 'Text lliure',
            default    => $type,
        };
    }
}
