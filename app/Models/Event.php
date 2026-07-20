<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasFactory;
    protected $fillable = [
        'colla_id',
        'name',
        'type',
        'start_date',
        'open_date',
        'close_date',
        'duration',
        'address',
        'visibility',
        'companions',
        'photo',
        'comments',
        'public_token',
        'recurring_group_id',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'open_date' => 'datetime',
            'close_date' => 'datetime',
            'type' => 'integer',
            'visibility' => 'boolean',
            'companions' => 'boolean',
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

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class)
            ->withPivot(['required', 'order'])
            ->orderByPivot('order');
    }

    public function boardSnapshots(): HasMany
    {
        return $this->hasMany(BoardSnapshot::class);
    }

    public function ensurePublicToken(): string
    {
        if (!$this->public_token) {
            $this->update(['public_token' => (string) Str::uuid()]);
        }

        return $this->public_token;
    }

    public static function typeLabel(int $type): string
    {
        return match ($type) {
            1 => 'Assaig',
            2 => 'Actuació',
            3 => 'Activitat',
            default => 'Altre',
        };
    }
}
