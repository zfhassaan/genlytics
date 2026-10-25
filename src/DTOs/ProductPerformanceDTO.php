<?php

namespace zfhassaan\genlytics\DTOs;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * Strongly-Typed DTO for Top Performing Products
 */
class ProductPerformanceDTO implements Arrayable, JsonSerializable
{
    public function __construct(
        public string $id,
        public string $name,
        public string $category,
        public float $revenue,
        public int $itemsPurchased,
        public int $itemsViewed,
        public int $itemsAddedToCart,
        public float $cartToViewRate = 0.0,
        public float $purchaseToViewRate = 0.0
    ) {
    }

    public static function fromRow(array $row): self
    {
        $dims = $row['dimensions'] ?? [];
        $mets = $row['metrics'] ?? [];

        $views = (int) ($mets['itemsViewed'] ?? 0);
        $carts = (int) ($mets['itemsAddedToCart'] ?? 0);
        $purchased = (int) ($mets['itemsPurchased'] ?? 0);

        $cartRate = $views > 0 ? round(($carts / $views) * 100, 2) : (float) ($mets['cartToViewRate'] ?? 0.0);
        $purchaseRate = $views > 0 ? round(($purchased / $views) * 100, 2) : (float) ($mets['purchaseToViewRate'] ?? 0.0);

        return new self(
            id: (string) ($dims['itemId'] ?? ''),
            name: (string) ($dims['itemName'] ?? $dims['itemId'] ?? 'Unknown Item'),
            category: (string) ($dims['itemCategory'] ?? 'Uncategorized'),
            revenue: (float) ($mets['itemRevenue'] ?? $mets['grossItemRevenue'] ?? 0.0),
            itemsPurchased: $purchased,
            itemsViewed: $views,
            itemsAddedToCart: $carts,
            cartToViewRate: $cartRate,
            purchaseToViewRate: $purchaseRate
        );
    }

    public function toArray(): array
    {
        return [
            'id'                    => $this->id,
            'name'                  => $this->name,
            'category'              => $this->category,
            'revenue'               => $this->revenue,
            'items_purchased'       => $this->itemsPurchased,
            'items_viewed'          => $this->itemsViewed,
            'items_added_to_cart'   => $this->itemsAddedToCart,
            'cart_to_view_rate'     => $this->cartToViewRate,
            'purchase_to_view_rate' => $this->purchaseToViewRate,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
