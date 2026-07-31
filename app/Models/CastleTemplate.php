<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CastleTemplate extends Model
{
    protected $fillable = ['colla_id', 'name', 'description', 'key', 'svg_path', 'positions', 'ready'];

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

    public function versions(): HasMany
    {
        return $this->hasMany(CastleTemplateVersion::class, 'template_id')->orderByDesc('created_at');
    }

    public function saveVersion(?string $label = null): CastleTemplateVersion
    {
        return $this->versions()->create([
            'positions' => $this->positions ?? [],
            'label'     => $label,
        ]);
    }
}
