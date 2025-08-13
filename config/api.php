<?php

declare(strict_types=1);

return [
    'allowed_ips' => env('API_ALLOWED_IPS', '127.0.0.1'),
    'rate_limit' => env('API_RATE_LIMIT', '60,1'), // 60 requests per minute
];
