<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Min;

class StockLevelData extends Data
{
    public function __construct(
        #[Required]
        public string $sku,
        #[Required]
        public string $warehouseId,
        public ?string $warehouseName = null,
        #[Min(0)]
        public int $quantityOnHand = 0,
        #[Min(0)]
        public int $quantityReserved = 0,
        #[Min(0)]
        public int $quantityAvailable = 0,
        #[Min(0)]
        public int $quantityOnOrder = 0,
        public ?string $lastUpdated = null,
        public ?string $externalId = null,
    ) {}

    public static function fromQuantities(
        string $sku,
        string $warehouseId,
        int $onHand,
        int $reserved = 0
    ): self {
        return new self(
            sku: $sku,
            warehouseId: $warehouseId,
            quantityOnHand: $onHand,
            quantityReserved: $reserved,
            quantityAvailable: max(0, $onHand - $reserved),
        );
    }

    public function hasAvailableStock(int $quantity): bool
    {
        return $this->quantityAvailable >= $quantity;
    }
}
