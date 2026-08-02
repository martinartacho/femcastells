<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'price',
        'member_price',
        'tax_rate',
        'stock',
        'active',
    ];

    protected $casts = [
        'price'        => 'decimal:2',
        'member_price' => 'decimal:2',
        'tax_rate'     => 'decimal:2',
        'active'       => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function priceFor(bool $isMember): float
    {
        if ($isMember && $this->member_price !== null) {
            return (float) $this->member_price;
        }

        return (float) $this->price;
    }

    public function priceWithTax(bool $isMember = false): float
    {
        return round($this->priceFor($isMember) * (1 + (float) $this->tax_rate / 100), 2);
    }

    public function isInStock(): bool
    {
        return $this->stock === null || $this->stock > 0;
    }
}
