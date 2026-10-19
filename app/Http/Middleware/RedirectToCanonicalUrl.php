<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Satu alamat resmi untuk seluruh situs: https://nusama3d.com.
 *
 * Variasi http://, www., atau kombinasi keduanya dialihkan permanen (301) ke
 * alamat resmi dengan path dan query yang sama, sehingga mesin pencari hanya
 * mengindeks satu versi. Lapisan pertamanya ada di public/.htaccess (juga
 * menangani berkas statis); middleware ini cadangan bila server tidak membaca
 * .htaccess, mis. Nginx.
 *
 * Hanya aktif bila config('app.canonical_url') terisi — default-nya APP_URL di
 * lingkungan production — sehingga localhost dan pengujian tidak terpengaruh.
 */
class RedirectToCanonicalUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        $canonical = config('app.canonical_url');

        if (! $canonical) {
            return $next($request);
        }

        $target = parse_url($canonical);
        $host = strtolower($target['host'] ?? '');
        $scheme = strtolower($target['scheme'] ?? 'https');

        if ($host === '') {
            return $next($request);
        }

        $sameHost = strtolower($request->getHost()) === $host;
        $sameScheme = $scheme !== 'https' || $this->isSecure($request);

        if ($sameHost && $sameScheme) {
            return $next($request);
        }

        return redirect()->to($scheme.'://'.$host.$request->getRequestUri(), 301);
    }

    /**
     * HTTPS juga dikenali dari header proxy/CDN (mis. Cloudflare) tanpa harus
     * memercayai seluruh proxy secara global — mencegah redirect berulang
     * ketika TLS diakhiri di depan server.
     */
    private function isSecure(Request $request): bool
    {
        if ($request->isSecure()) {
            return true;
        }

        if (strtolower((string) $request->header('X-Forwarded-Proto')) === 'https') {
            return true;
        }

        return str_contains((string) $request->header('CF-Visitor'), '"https"');
    }
}
