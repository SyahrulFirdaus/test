<?php

namespace App\Http\Middleware;

use App\Support\LeadTracking;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Simpan sumber kedatangan pengunjung (GCLID/UTM, halaman masuk, referrer)
 * pada kunjungan pertama ke halaman publik. Lihat App\Support\LeadTracking.
 *
 * Hanya mencatat — tidak pernah mengubah respons selain menambah cookie.
 */
class CaptureLeadTracking
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->applies($request, $response)) {
            return $response;
        }

        $data = LeadTracking::capture($request);

        if ($data !== null) {
            $response->headers->setCookie(Cookie::make(
                LeadTracking::COOKIE,
                json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                LeadTracking::LIFETIME,
                null,
                null,
                null,
                true,
                false,
                'lax',
            ));
        }

        return $response;
    }

    private function applies(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET') || $request->ajax() || $request->expectsJson()) {
            return false;
        }

        // Halaman pengelola dan dashboard pelanggan bukan halaman masuk pengunjung.
        if ($request->is('admin', 'admin/*', 'superadmin', 'superadmin/*', 'dashboard', 'dashboard/*', 'up', 'sitemap.xml', 'robots.txt')) {
            return false;
        }

        return $response->isSuccessful()
            && str_contains((string) $response->headers->get('Content-Type'), 'text/html');
    }
}
