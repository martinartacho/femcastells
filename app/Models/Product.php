<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'colla_id',
        'name',
        'description',
        'image',
        'active',
        'members_only',
    ];

    protected $casts = [
        'active'       => 'boolean',
        'members_only' => 'boolean',
    ];

    public function colla(): BelongsTo
    {
        return $this->belongsTo(Colla::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }
}
