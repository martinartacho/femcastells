<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $table = 'attendance';

    protected $fillable = [
        'event_id',
        'casteller_id',
        'status',
        'companions',
        'checked_in',
        'checked_in_at',
    ];

    protected $casts = [
        'checked_in'    => 'boolean',
        'checked_in_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function casteller(): BelongsTo
    {
        return $this->belongsTo(Casteller::class);
    }

    public function answers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EventAnswer::class);
    }
}
