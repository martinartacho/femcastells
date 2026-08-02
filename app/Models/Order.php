<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    public const STATUS_PENDING   = 'pending';
    public const STATUS_PAID      = 'paid';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED  = 'refunded';

    public const STATUSES = [
        self::STATUS_PENDING   => 'Pendent',
        self::STATUS_PAID      => 'Pagat',
        self::STATUS_CANCELLED => 'Cancel·lat',
        self::STATUS_REFUNDED  => 'Reemborsat',
    ];

    protected $fillable = [
        'colla_id',
        'casteller_id',
        'buyer_name',
        'buyer_email',
        'status',
        'subtotal',
        'tax_total',
        'total',
        'stripe_checkout_session_id',
        'stripe_payment_intent_id',
        'paid_at',
    ];

    protected $casts = [
        'subtotal'  => 'decimal:2',
        'tax_total' => 'decimal:2',
        'total'     => 'decimal:2',
        'paid_at'   => 'datetime',
    ];

    public function colla(): BelongsTo
    {
        return $this->belongsTo(Colla::class);
    }

    public function casteller(): BelongsTo
    {
        return $this->belongsTo(Casteller::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function buyerLabel(): string
    {
        return $this->casteller?->alias ?? $this->buyer_name ?? '—';
    }
}
