<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = base64_encode(random_bytes(16));
        view()->share('cspNonce', $nonce);

        $response = $next($request);

    	$allowedDomains = [
        	"'self'",
        	'https://www.yourdomain.com',
        	'https://*.yourdomain.com'
    	];

        $cspDirectives = [
            "default-src 'self'",
            "script-src 'self' https://cdn.jsdelivr.net 'nonce-$nonce'",
            "style-src 'self' https://cdn.jsdelivr.net 'nonce-$nonce'",
            "img-src 'self' data:",
            "font-src 'self' https://cdn.jsdelivr.net",
            "connect-src 'self' https://*.yourdomain.com https://*.yourdomain.com",
            "frame-src 'none'",
            "object-src 'none'",
            "frame-ancestors 'self' https://yourdomain.com https://yourdomain.com https://*.yourdomain.com https://*.yourdomain.com"
        ];

        $response->headers->set('Content-Security-Policy', implode('; ', $cspDirectives));
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin');

        return $response;
    }
}
