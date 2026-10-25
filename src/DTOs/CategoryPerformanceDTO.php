<?php

namespace zfhassaan\genlytics\DTOs;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * Strongly-Typed DTO for Top Performing Product Categories
 */
class CategoryPerformanceDTO implements Arrayable, JsonSerializable
{
    public function __construct(
        public readonly string $category,
        public readonly float $revenue,
        public readonly int $itemsPurchased,
        public readonly int $itemsViewed,
        public readonly int $itemsAddedToCart,
        public readonly float $cartToViewRate = 0.0
    ) {
    }

    public static function fromRow(array $row): self
    {
        $dims = $row['dimensions'] ?? [];
        $mets = $row['metrics'] ?? [];

        $views = (int) ($mets['itemsViewed'] ?? 0);
        $carts = (int) ($mets['itemsAddedToCart'] ?? 0);
        $cartRate = $views > 0 ? round(($carts / $views) * 100, 2) : (float) ($mets['cartToViewRate'] ?? 0.0);

        return new self(
            category: (string) ($dims['itemCategory'] ?? 'Uncategorized'),
            revenue: (float) ($mets['itemRevenue'] ?? $mets['grossItemRevenue'] ?? 0.0),
            itemsPurchased: (int) ($mets['itemsPurchased'] ?? 0),
            itemsViewed: $views,
            itemsAddedToCart: $carts,
            cartToViewRate: $cartRate
        );
    }

    public function toArray(): array
    {
        return [
            'category'            => $this->category,
            'revenue'             => $this->revenue,
            'items_purchased'     => $this->itemsPurchased,
            'items_viewed'        => $this->itemsViewed,
            'items_added_to_cart' => $this->itemsAddedToCart,
            'cart_to_view_rate'   => $this->cartToViewRate,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}

