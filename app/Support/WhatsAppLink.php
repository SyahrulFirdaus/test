<?php

namespace App\Support;

use App\Models\CompanyProfile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

/**
 * Tautan WhatsApp dengan teks otomatis per halaman.
 *
 *   "Halo NUSAMA3D, saya ingin konsultasi Reverse Engineering
 *    (dari halaman nusama3d.com/services/reverse-engineering)"
 *
 * Urutan sumber teks:
 *  1. @section('wa-text')  — teks utuh dari halaman (mis. landing page);
 *  2. @section('wa-topic') — topik saja, kalimatnya disusun di sini;
 *  3. topik bawaan per route (lihat topicForRoute()).
 *
 * Bila pengunjung datang dari iklan Google (GCLID di URL atau cookie first
 * touch), "[Google]" ditambahkan di akhir pesan agar tim sales tahu sumbernya.
 */
class WhatsAppLink
{
    /** Topik bawaan per nama route. */
    private const TOPICS = [
        'home' => 'kebutuhan 3D printing',
        'services' => 'layanan 3D printing & engineering',
        'technologies' => 'teknologi 3D printing',
        'industries' => '3D printing untuk industri',
        'models' => 'cetak file 3D',
        'models.guide' => 'cetak file 3D',
        'models.viewer' => 'cetak file 3D',
        'models.viewer.show' => 'cetak file 3D',
        'about' => 'kebutuhan 3D printing',
        'tracking.index' => 'status penawaran',
        'tracking.show' => 'status penawaran',
        'thanks' => 'permintaan yang baru saya kirim',
        'legal.privacy' => 'kebijakan privasi',
        'legal.terms' => 'syarat & ketentuan layanan',
    ];

    /** Teks pesan untuk halaman yang sedang dibuka. */
    public static function message(?string $topic = null): string
    {
        $pageText = trim(html_entity_decode(View::yieldContent('wa-text'), ENT_QUOTES));

        if ($pageText !== '') {
            return $pageText;
        }

        $topic ??= trim(html_entity_decode(View::yieldContent('wa-topic'), ENT_QUOTES)) ?: self::topicForRoute();

        return 'Halo '.self::company()?->name.', saya ingin konsultasi '.$topic.' (dari halaman '.self::pageLabel().')';
    }

    /**
     * URL wa.me lengkap dengan teks; null bila nomor WhatsApp belum diatur
     * di Profil Perusahaan.
     */
    public static function url(?string $text = null): ?string
    {
        $base = self::company()?->whatsapp_link;

        if (blank($base)) {
            return null;
        }

        $text ??= self::message();

        if (self::fromGoogle() && ! str_contains($text, '[Google]')) {
            $text .= ' [Google]';
        }

        return $base.'?text='.rawurlencode($text);
    }

    /** URL WhatsApp, atau bagian kontak halaman About bila nomornya belum ada. */
    public static function href(?string $text = null): string
    {
        return self::url($text) ?? route('about').'#kontak';
    }

    /** Pengunjung datang dari klik iklan Google. */
    public static function fromGoogle(): bool
    {
        $request = request();

        foreach (['gclid', 'gbraid', 'wbraid'] as $key) {
            if (filled($request->query($key))) {
                return true;
            }
        }

        return LeadTracking::fromGoogleAds(LeadTracking::fromRequest($request));
    }

    /** Alamat halaman tanpa skema dan query, mis. "nusama3d.com/services". */
    public static function pageLabel(): string
    {
        $url = url()->current();

        return preg_replace('#^https?://(www\.)?#', '', $url);
    }

    private static function topicForRoute(): string
    {
        return self::TOPICS[Route::currentRouteName()] ?? 'kebutuhan 3D printing';
    }

    private static function company(): ?CompanyProfile
    {
        return app(CompanyProfile::class);
    }
}
