<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustHosts as Middleware;

class TrustHosts extends Middleware
{
    /**
     * Get the host patterns that should be trusted.
     *
     * @return array<int, string|null>
     */
    public function hosts(): array
    {
        $hosts = [$this->allSubdomainsOfApplicationUrl()];

        if (app()->environment(['local', 'testing']) || PHP_SAPI === 'cli-server') {
            $hosts[] = '^localhost$';
            $hosts[] = '^127\.0\.0\.1$';
            $hosts[] = '^\[::1\]$';
        }

        return $hosts;
    }
}
