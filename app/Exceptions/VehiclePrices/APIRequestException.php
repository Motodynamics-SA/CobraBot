<?php

declare(strict_types=1);

namespace App\Exceptions\VehiclePrices;

class APIRequestException extends VehiclePricesAPIException {
    /**
     * @param  array<string, mixed>|null  $context
     */
    public function __construct(
        string $message = 'API request failed',
        int $code = 0,
        ?\Exception $exception = null,
        ?array $context = null,
        private readonly int $httpStatus = 0
    ) {
        parent::__construct($message, $code, $exception, $context);
    }

    public function getHttpStatus(): int {
        return $this->httpStatus;
    }
}
