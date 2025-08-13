<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class ExternalAPIException extends Exception {
    public function __construct(string $message = '', int $code = 0, ?Exception $exception = null) {
        if (app()->environment('production')) {
            $message = 'An error occurred while communicating with an external service.';
        }

        parent::__construct($message, $code, $exception);
    }
}
