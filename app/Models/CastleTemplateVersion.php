<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CastleTemplateVersion extends Model
{
    public $timestamps = false;

    protected $fillable = ['template_id', 'positions', 'label', 'created_at'];

    protected function casts(): array
    {
        return [
            'positions'  => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CastleTemplate::class, 'template_id');
    }
}
