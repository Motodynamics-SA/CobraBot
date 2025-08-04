<?php

namespace App\Exceptions\VehiclePrices;

class AuthenticationException extends TokenServiceException {
    /**
     * @param  array<string, mixed>|null  $context
     */
    public function __construct(
        string $message = 'Failed to authenticate with the vehicle prices API',
        int $code = 401,
        ?\Exception $previous = null,
        ?array $context = null
    ) {
        parent::__construct($message, $code, $previous, $context);
    }
}
