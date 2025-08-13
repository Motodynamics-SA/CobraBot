<?php

declare(strict_types=1);

namespace App\Exceptions\VehiclePrices;

use App\Exceptions\ExternalAPIException;

class VehiclePricesAPIException extends ExternalAPIException {
    /**
     * @param  array<string, mixed>|null  $context
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Exception $exception = null,
        private readonly ?array $context = null
    ) {
        parent::__construct($message, $code, $exception);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getContext(): ?array {
        return $this->context;
    }
}
