<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Suggeriment extends Model
{
    protected $fillable = ['colla_id', 'casteller_id', 'text'];

    public function colla(): BelongsTo
    {
        return $this->belongsTo(Colla::class);
    }

    public function casteller(): BelongsTo
    {
        return $this->belongsTo(Casteller::class);
    }
}
