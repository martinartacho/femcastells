<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Period extends Model
{
    protected $fillable = ['colla_id', 'name', 'start_date', 'end_date'];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    public function colla(): BelongsTo
    {
        return $this->belongsTo(Colla::class);
    }

    public function isActive(): bool
    {
        return now()->between($this->start_date, $this->end_date);
    }
}
