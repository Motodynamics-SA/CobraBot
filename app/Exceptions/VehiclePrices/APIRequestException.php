<?php

namespace App\Exceptions\VehiclePrices;

class APIRequestException extends VehiclePricesAPIException {
    /**
     * @param  array<string, mixed>|null  $context
     */
    public function __construct(
        string $message = 'API request failed',
        int $code = 0,
        ?\Exception $previous = null,
        ?array $context = null,
        private int $httpStatus = 0
    ) {
        parent::__construct($message, $code, $previous, $context);
    }

    public function getHttpStatus(): int {
        return $this->httpStatus;
    }
}
