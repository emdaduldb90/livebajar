<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $isLoopback = in_array(strtolower($request->getHost()), ['localhost', '127.0.0.1', '::1', '[::1]'], true);

        if (PHP_SAPI === 'cli-server' && $isLoopback) {
            config(['session.secure' => $request->isSecure()]);
        } elseif (app()->environment('production')) {
            config(['session.secure' => true]);
        }

        $nonce = base64_encode(random_bytes(18));
        $request->attributes->set('csp_nonce', $nonce);

        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        $response->headers->set(
            'Content-Security-Policy',
            implode('; ', [
                "default-src 'self'",
                "base-uri 'self'",
                "form-action 'self'",
                "frame-ancestors 'self'",
                "object-src 'none'",
                "script-src 'self' 'nonce-{$nonce}' https://connect.facebook.net https://analytics.tiktok.com https://www.googletagmanager.com",
                "script-src-attr 'none'",
                "style-src 'self' 'nonce-{$nonce}' https://fonts.googleapis.com https://fonts.bunny.net",
                "style-src-attr 'none'",
                "font-src 'self' https://fonts.gstatic.com https://fonts.bunny.net",
                "img-src 'self' https: data:",
                "connect-src 'self' https://connect.facebook.net https://www.facebook.com https://graph.facebook.com https://analytics.tiktok.com https://business-api.tiktok.com https://www.googletagmanager.com https://www.google-analytics.com https://region1.google-analytics.com https://stats.g.doubleclick.net https://www.google.com https://google.com https://www.googleadservices.com https://googleads.g.doubleclick.net",
            ])
        );

        if ($request->is('admin') || $request->is('admin/*')) {
            $response->headers->set('Cache-Control', 'private, no-store, max-age=0, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
            $response->headers->set('Content-Security-Policy', $response->headers->get('Content-Security-Policy').'; upgrade-insecure-requests');
        }

        return $response;
    }
}
