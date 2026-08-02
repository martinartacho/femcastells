<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProductVariant;
use Illuminate\Support\Collection;

class Cart
{
    private const SESSION_PREFIX = 'botiga_cart';

    public function __construct(private readonly int $collaId)
    {
    }

    public function add(int $variantId, int $quantity = 1): void
    {
        $items = $this->rawItems();
        $items[$variantId] = ($items[$variantId] ?? 0) + max(1, $quantity);
        $this->putRawItems($items);
    }

    public function update(int $variantId, int $quantity): void
    {
        $items = $this->rawItems();

        if ($quantity <= 0) {
            unset($items[$variantId]);
        } else {
            $items[$variantId] = $quantity;
        }

        $this->putRawItems($items);
    }

    public function remove(int $variantId): void
    {
        $this->update($variantId, 0);
    }

    public function clear(): void
    {
        session()->forget($this->sessionKey());
    }

    public function count(): int
    {
        return array_sum($this->rawItems());
    }

    /**
     * @return Collection<int, array{variant: ProductVariant, quantity: int}>
     */
    public function lines(bool $isMember): Collection
    {
        $items = $this->rawItems();

        if (empty($items)) {
            return collect();
        }

        $variants = ProductVariant::with('product')
            ->whereIn('id', array_keys($items))
            ->whereHas('product', fn ($q) => $q->where('colla_id', $this->collaId))
            ->get()
            ->keyBy('id');

        return collect($items)
            ->filter(fn ($qty, $variantId) => $variants->has($variantId))
            ->map(fn ($qty, $variantId) => [
                'variant'  => $variants[$variantId],
                'quantity' => $qty,
            ])
            ->values();
    }

    public function subtotal(bool $isMember): float
    {
        return round($this->lines($isMember)->sum(
            fn ($line) => $line['variant']->priceFor($isMember) * $line['quantity']
        ), 2);
    }

    public function taxTotal(bool $isMember): float
    {
        return round($this->lines($isMember)->sum(function ($line) use ($isMember) {
            $base = $line['variant']->priceFor($isMember) * $line['quantity'];

            return $base * ((float) $line['variant']->tax_rate / 100);
        }), 2);
    }

    public function total(bool $isMember): float
    {
        return round($this->subtotal($isMember) + $this->taxTotal($isMember), 2);
    }

    /**
     * @return array<int, int>
     */
    private function rawItems(): array
    {
        return session($this->sessionKey(), []);
    }

    /**
     * @param array<int, int> $items
     */
    private function putRawItems(array $items): void
    {
        session([$this->sessionKey() => $items]);
    }

    private function sessionKey(): string
    {
        return self::SESSION_PREFIX . '.' . $this->collaId;
    }
}
