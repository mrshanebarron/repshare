<?php

namespace App\Data;

use Carbon\Carbon;

readonly class WholesaleOrderData
{
    public function __construct(
        public string $externalId,
        public ?int $localId = null,
        public string $status = 'pending',
        public ?string $customerReference = null,
        public ?float $totalAmount = null,
        public ?Carbon $submittedAt = null,
        public ?Carbon $confirmedAt = null,
        public ?Carbon $shippedAt = null,
        public ?string $trackingNumber = null,
        public ?string $carrierName = null,
        public ?string $errorMessage = null,
    ) {}
}
