<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CastleTemplate extends Model
{
    protected $fillable = ['colla_id', 'name', 'key', 'svg_path', 'positions', 'ready'];

    protected function casts(): array
    {
        return [
            'positions' => 'array',
            'ready'     => 'boolean',
        ];
    }

    public function colla(): BelongsTo
    {
        return $this->belongsTo(Colla::class);
    }
}
