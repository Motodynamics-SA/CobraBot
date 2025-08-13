<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware {
    protected $proxies = '*'; // or list specific proxy IPs

    /** @phpstan-ignore-next-line */
    protected $headers = Request::HEADER_X_FORWARDED_ALL;
}
