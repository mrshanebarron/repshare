<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Min;

class ProductData extends Data
{
    public function __construct(
        #[Required]
        public string $sku,
        #[Required]
        public string $name,
        public ?string $description = null,
        public ?string $brandId = null,
        public ?string $brandName = null,
        public ?string $category = null,
        public ?string $subcategory = null,
        #[Min(0)]
        public float $unitPrice = 0,
        public int $packSize = 1,
        public int $caseSize = 1,
        public ?string $imageUrl = null,
        public ?float $alcoholPercent = null,
        public ?string $countryOfOrigin = null,
        public ?string $region = null,
        public bool $isActive = true,
        public ?string $externalId = null,
        public array $metadata = [],
    ) {}

    public function casePrice(): float
    {
        return $this->unitPrice * $this->caseSize;
    }
}
