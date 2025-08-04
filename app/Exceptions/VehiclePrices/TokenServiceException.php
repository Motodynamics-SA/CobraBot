<?php

namespace App\Exceptions\VehiclePrices;

use App\Exceptions\ExternalAPIException;

class TokenServiceException extends ExternalAPIException {
    /**
     * @param  array<string, mixed>|null  $context
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Exception $previous = null,
        private ?array $context = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getContext(): ?array {
        return $this->context;
    }
}
