<?php

namespace App\Http\Controllers;

use App\Models\QuotationRequest;
use App\Support\BusinessHours;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Halaman Terima Kasih (/terima-kasih?tipe=kontak|quote) setelah formulir
 * berhasil dikirim. Tidak diindeks mesin pencari (meta robots + header).
 *
 * Event `generate_lead` (form_location, service, user_data) hanya dikirim bila
 * sesi membawa penanda kiriman formulir yang benar-benar tersimpan (flash
 * `lead_submitted`), sehingga memuat ulang atau membuka halaman ini langsung
 * tidak menghitung lead ganda. Email & HP untuk Enhanced Conversions dibaca
 * dari sesi, tidak pernah dari URL.
 */
class ThanksController extends Controller
{
    public function show(Request $request): Response
    {
        $type = $request->query('tipe') === 'quote' ? 'quote' : 'kontak';
        $submitted = $request->session()->get('lead_submitted');

        // Nomor tracking hanya ditampilkan untuk quotation yang baru saja
        // dikirim dari sesi ini, bukan nomor apa pun dari URL.
        $quotation = null;

        if ($type === 'quote' && is_array($submitted) && filled($submitted['tracking_number'] ?? null)) {
            $quotation = QuotationRequest::query()->where('tracking_number', $submitted['tracking_number'])->first();
        }

        // Disimpan ulang agar tautan tracking tetap ada bila halaman disegarkan,
        // tanpa ikut mengirim ulang event.
        if ($quotation !== null) {
            $request->session()->put('thanks_quote', $quotation->tracking_number);
        } elseif ($type === 'quote' && filled($number = $request->session()->get('thanks_quote'))) {
            $quotation = QuotationRequest::query()->where('tracking_number', $number)->first();
        }

        return response()
            ->view('pages.thanks', [
                'type' => $type,
                'quotation' => $quotation,
                'replyEstimate' => BusinessHours::replyEstimate(),
                // generate_lead hanya untuk formulir (tipe=kontak); quotation
                // sudah dilaporkan sebagai quote_submitted di halaman 3D Models.
                'leadEvent' => $type === 'kontak' && is_array($submitted) && ($submitted['tipe'] ?? null) === 'kontak'
                    ? array_filter([
                        'form_location' => $submitted['form_location'] ?? null,
                        'service' => $submitted['service'] ?? null,
                        'form_name' => $submitted['form'] ?? null,
                        'user_data' => $submitted['user_data'] ?? null,
                    ])
                    : null,
            ])
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
