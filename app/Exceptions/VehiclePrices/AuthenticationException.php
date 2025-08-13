<?php

declare(strict_types=1);

namespace App\Exceptions\VehiclePrices;

class AuthenticationException extends VehiclePricesAPIException {
    /**
     * @param  array<string, mixed>|null  $context
     */
    public function __construct(
        string $message = 'Failed to authenticate with the vehicle prices API',
        int $code = 401,
        ?\Exception $exception = null,
        ?array $context = null
    ) {
        parent::__construct($message, $code, $exception, $context);
    }
}
