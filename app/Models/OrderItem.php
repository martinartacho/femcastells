<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_variant_id',
        'product_name',
        'variant_name',
        'unit_price',
        'tax_rate',
        'quantity',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'tax_rate'   => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function lineTotal(): float
    {
        return round($this->unitPriceWithTax() * $this->quantity, 2);
    }

    public function unitPriceWithTax(): float
    {
        return round((float) $this->unit_price * (1 + (float) $this->tax_rate / 100), 2);
    }
}
