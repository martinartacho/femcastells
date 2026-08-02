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

    public const PAYMENT_METHOD_STRIPE = 'stripe';
    public const PAYMENT_METHOD_BIZUM  = 'bizum';

    public const PAYMENT_METHODS = [
        self::PAYMENT_METHOD_STRIPE => 'Targeta (Stripe)',
        self::PAYMENT_METHOD_BIZUM  => 'Bizum',
    ];

    protected $fillable = [
        'colla_id',
        'casteller_id',
        'buyer_name',
        'buyer_email',
        'notes',
        'status',
        'payment_method',
        'access_token',
        'reference_code',
        'subtotal',
        'tax_total',
        'total',
        'stripe_checkout_session_id',
        'stripe_payment_intent_id',
        'paid_at',
        'buyer_confirmed_at',
    ];

    protected $casts = [
        'subtotal'           => 'decimal:2',
        'tax_total'          => 'decimal:2',
        'total'               => 'decimal:2',
        'paid_at'             => 'datetime',
        'buyer_confirmed_at'  => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            $order->reference_code ??= self::generateReferenceCode();
        });
    }

    public static function generateReferenceCode(): string
    {
        // Alfabet sense caràcters ambigus (0/O, 1/I/L)
        $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

        do {
            $code = collect(range(1, 6))
                ->map(fn () => $alphabet[random_int(0, strlen($alphabet) - 1)])
                ->implode('');
        } while (self::where('reference_code', $code)->exists());

        return $code;
    }

    public function formattedReferenceCode(): string
    {
        if (blank($this->reference_code)) {
            return '#' . $this->id;
        }

        return substr($this->reference_code, 0, 3) . '-' . substr($this->reference_code, 3, 3);
    }

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

    public function isBizum(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_BIZUM;
    }

    public function isStripe(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_STRIPE;
    }
}
