<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Min;

class OrderLineData extends Data
{
    public function __construct(
        #[Required]
        public string $sku,
        public ?string $productName = null,
        public ?string $brandId = null,
        public ?string $brandName = null,
        #[Required, Min(1)]
        public int $quantity = 1,
        public float $unitPrice = 0,
        public float $lineTotal = 0,
        public float $discountPercent = 0,
        public float $discountAmount = 0,
        public ?string $warehouseId = null,
        public ?string $warehouseName = null,
        public ?string $notes = null,
    ) {}

    public function calculateLineTotal(): float
    {
        $subtotal = $this->quantity * $this->unitPrice;
        $discount = $subtotal * ($this->discountPercent / 100);
        return $subtotal - $discount;
    }

    public static function fromProduct(ProductData $product, int $quantity): self
    {
        return new self(
            sku: $product->sku,
            productName: $product->name,
            brandId: $product->brandId,
            brandName: $product->brandName,
            quantity: $quantity,
            unitPrice: $product->unitPrice,
            lineTotal: $product->unitPrice * $quantity,
        );
    }
}
