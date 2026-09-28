<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\DashboardMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Switch Personal/Business pada header dashboard pengelola.
 *
 * Yang berubah hanya TAMPILAN — segmen pelanggan yang diringkas dashboard dan
 * menu pembayaran yang relevan baginya. Tidak ada role maupun hak akses yang
 * ikut berubah, jadi route ini sengaja tidak dijaga `admin.permission`: siapa
 * pun yang sudah boleh membuka dashboard boleh mengganti cara melihatnya.
 *
 * Dipakai bersama Admin dan Superadmin lewat $staffRoutes, sama seperti menu
 * operasional lainnya.
 */
class DashboardModeController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mode' => ['required', 'string', Rule::in(DashboardMode::keys())],
        ]);

        $mode = DashboardMode::set($validated['mode']);

        // Kembali ke halaman asalnya: switch dapat ditekan dari mana saja,
        // bukan hanya dari halaman dashboard.
        return back()->with('status', 'Dashboard beralih ke mode '.DashboardMode::label($mode).'.');
    }
}
