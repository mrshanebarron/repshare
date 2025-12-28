<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;

class WarehouseData extends Data
{
    public function __construct(
        #[Required]
        public string $id,
        #[Required]
        public string $name,
        public ?string $code = null,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $state = null,
        public ?string $postcode = null,
        public ?string $country = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?string $threePlId = null,
        public ?string $threePlName = null,
        public ?string $contactName = null,
        public ?string $contactEmail = null,
        public ?string $contactPhone = null,
        public bool $isActive = true,
        public ?string $externalId = null,
        public array $metadata = [],
    ) {}

    public function fullAddress(): string
    {
        return collect([
            $this->address,
            $this->city,
            $this->state,
            $this->postcode,
            $this->country,
        ])->filter()->implode(', ');
    }
}
