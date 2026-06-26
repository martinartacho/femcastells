<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $fillable = [
        'colla_id',
        'name',
        'value',
        'group',
        'type',
    ];

    public function colla(): BelongsTo
    {
        return $this->belongsTo(Colla::class);
    }

    public function castellers(): BelongsToMany
    {
        return $this->belongsToMany(Casteller::class);
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class);
    }
}
