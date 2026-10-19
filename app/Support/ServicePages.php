<?php

namespace App\Support;

/**
 * Pemetaan layanan (tabel `services`) ke landing page-nya di
 * /services/{page} — lihat config/service-pages.php.
 */
final class ServicePages
{
    /** @return array<string, string> slug layanan di database → URL landing page */
    public static function urls(): array
    {
        return collect(config('service-pages.pages'))
            ->mapWithKeys(fn (array $config, string $page) => [$config['service'] => route('services.show', $page)])
            ->all();
    }

    /** URL landing page satu layanan; bila belum ada, anchor di /services. */
    public static function url(string $serviceSlug): string
    {
        return self::urls()[$serviceSlug] ?? route('services').'#'.$serviceSlug;
    }
}
