<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Noticia extends Model
{
    protected $table = 'noticies';

    protected $fillable = [
        'colla_id', 'tag_id', 'title', 'summary', 'body',
        'labels', 'version', 'published_at',
    ];

    protected $casts = [
        'labels'       => 'array',
        'published_at' => 'datetime',
    ];

    public const LABELS = [
        'general'    => 'General',
        'pissarra'   => 'Pissarra',
        'castellers' => 'Castellers',
        'events'     => 'Esdeveniments',
        'admin'      => 'Administració',
        'sistema'    => 'Sistema',
    ];

    public function colla(): BelongsTo
    {
        return $this->belongsTo(Colla::class);
    }

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeGlobal(Builder $query): Builder
    {
        return $query->whereNull('colla_id');
    }

    public function scopeVisibleTo(Builder $query, ?int $collaId): Builder
    {
        return $query->where(fn(Builder $q) => $q
            ->whereNull('colla_id')
            ->orWhere('colla_id', $collaId)
        );
    }

    public function hasLabel(string $label): bool
    {
        return in_array($label, $this->labels ?? [], true);
    }
}
