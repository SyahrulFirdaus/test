<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Verifikasi token reCAPTCHA v3 (config leads.recaptcha).
 *
 * Selama kuncinya belum diisi, verifikasi dianggap lolos dan formulir hanya
 * dilindungi honeypot + batas waktu pengisian. Bila Google tidak dapat
 * dihubungi, kiriman juga diloloskan — lebih baik satu spam masuk daripada
 * calon pelanggan yang tertolak.
 */
class Recaptcha
{
    public function enabled(): bool
    {
        return filled(config('leads.recaptcha.site_key')) && filled(config('leads.recaptcha.secret_key'));
    }

    public function passes(?string $token, string $action, ?string $ip = null): bool
    {
        if (! $this->enabled()) {
            return true;
        }

        if (blank($token)) {
            return false;
        }

        try {
            $result = Http::asForm()
                ->timeout(5)
                ->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => config('leads.recaptcha.secret_key'),
                    'response' => $token,
                    'remoteip' => $ip,
                ])
                ->json();
        } catch (Throwable $exception) {
            Log::warning('reCAPTCHA tidak dapat diverifikasi: '.$exception->getMessage());

            return true;
        }

        return ($result['success'] ?? false)
            && ($result['action'] ?? $action) === $action
            && (float) ($result['score'] ?? 0) >= (float) config('leads.recaptcha.min_score');
    }
}
