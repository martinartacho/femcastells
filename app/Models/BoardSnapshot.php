<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoardSnapshot extends Model
{
    protected $fillable = [
        'colla_id',
        'event_id',
        'castle_type',
        'name',
        'assignments',
    ];

    protected function casts(): array
    {
        return [
            'assignments' => 'array',
        ];
    }

    public function colla(): BelongsTo
    {
        return $this->belongsTo(Colla::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
