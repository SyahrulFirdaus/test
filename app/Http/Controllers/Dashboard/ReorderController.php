<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\QuotationItem;
use App\Models\QuotationRequest;
use App\Models\User;
use App\Notifications\NewQuotationSubmitted;
use App\Support\QuotationStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Pesan ulang pekerjaan yang pernah selesai — khusus pelanggan Business.
 *
 * Penawaran baru disusun dari spesifikasi pesanan sebelumnya: berkas model,
 * teknologi, material, resolusi, support, finishing, dan jumlahnya disalin apa
 * adanya, lalu masuk kembali ke tahap paling awal sehingga admin tetap
 * meninjaunya seperti penawaran biasa.
 *
 * Berkasnya benar-benar disalin, bukan sekadar dirujuk ulang, agar penawaran
 * lama tetap dapat dihapus tanpa membuat pesanan barunya kehilangan model.
 */
class ReorderController extends Controller
{
    public function store(Request $request, QuotationRequest $quotation): RedirectResponse
    {
        abort_unless($quotation->user_id === $request->user()->id, 404);

        $quotation->load('items');

        if ($quotation->items->isEmpty()) {
            return back()->with('error', 'Penawaran ini tidak memiliki model yang dapat dipesan ulang.');
        }

        // Berkas yang sudah tidak ada di penyimpanan tidak dapat dipesan ulang;
        // lebih baik ditolak di sini daripada gagal saat produksi.
        $missing = $quotation->items->filter(fn (QuotationItem $item) => ! $item->fileExists());

        if ($missing->isNotEmpty()) {
            return back()->with('error', 'Berkas model '.$missing->first()->file_name
                .' tidak lagi tersedia di penyimpanan, sehingga penawaran ini tidak dapat dipesan ulang. '
                .'Silakan unggah ulang model dari halaman 3D Models.');
        }

        $copy = DB::transaction(function () use ($quotation, $request) {
            $items = $quotation->items->map(fn (QuotationItem $item) => [
                ...$this->itemAttributes($item),
                'file_path' => $this->copyFile($item),
            ]);

            $copy = QuotationRequest::create([
                'user_id' => $request->user()->id,
                'tracking_number' => QuotationRequest::generateTrackingNumber(),

                // Identitas dibaca ulang dari akun sekarang, bukan disalin dari
                // penawaran lama yang mungkin sudah kedaluwarsa datanya.
                'name' => $request->user()->name,
                'email' => $request->user()->email,
                'whatsapp' => (string) $request->user()->phone,
                // Dibaca ulang dari profil perusahaan, bukan disalin dari
                // penawaran lama, agar perusahaan yang berganti nama tidak
                // membawa nama lamanya ke pesanan baru.
                'company' => $request->user()->businessProfile?->company_name ?? $quotation->company,
                'notes' => $quotation->notes,

                'address_id' => $quotation->address_id,
                'shipping_address' => $quotation->shipping_address,

                ...QuotationRequest::summaryFrom($items),

                'status' => QuotationStatus::first(),
            ]);

            $items->each(fn (array $item) => $copy->items()->create($item));

            return $copy;
        });

        $copy->recordHistory(
            QuotationStatus::first(),
            'Pesanan ulang dari penawaran '.$quotation->tracking_number.'.',
            $request->user()->name,
        );

        Notification::send(User::admins()->get(), new NewQuotationSubmitted($copy));

        return redirect()
            ->route('dashboard.quotations.show', $copy)
            ->with('status', 'Pesanan ulang berhasil dibuat dengan nomor '.$copy->tracking_number
                .'. Spesifikasinya mengikuti penawaran '.$quotation->tracking_number
                .' dan sekarang menunggu review admin.');
    }

    /**
     * Spesifikasi satu model beserta estimasi biayanya.
     *
     * Estimasi biaya sistem ikut disalin karena dihitung dari spesifikasi yang
     * sama; harga penawaran pesanan baru kemudian ditetapkan dari penjumlahan
     * estimasi itu lewat QuotationRequest::summaryFrom().
     *
     * @return array<string, mixed>
     */
    private function itemAttributes(QuotationItem $item): array
    {
        return [
            'position' => $item->position,
            'file_name' => $item->file_name,
            'file_format' => $item->file_format,
            'file_size' => $item->file_size,
            'model_stats' => $item->model_stats,
            'analysis_status' => $item->analysis_status,
            'analysis' => $item->analysis,

            'technology' => $item->technology,
            'material' => $item->material,
            'printer' => $item->printer,
            'printer_name' => $item->printer_name,
            'build_volume' => $item->build_volume,

            'quantity' => $item->quantity,
            'scale_percent' => $item->scale_percent,
            'resolution' => $item->resolution,
            'layer_height_mm' => $item->layer_height_mm,
            'infill_density' => $item->infill_density,
            'infill_pattern' => $item->infill_pattern,
            'support_enabled' => $item->support_enabled,
            'support_type' => $item->support_type,
            'hollow_enabled' => $item->hollow_enabled,
            'hollow_wall_thickness_mm' => $item->hollow_wall_thickness_mm,
            'hollow_drain_diameter_mm' => $item->hollow_drain_diameter_mm,
            'hollow_drain_position' => $item->hollow_drain_position,
            'material_color' => $item->material_color,
            'finishing' => $item->finishing,
            'fits_build_volume' => $item->fits_build_volume,

            'model_volume_cm3' => $item->model_volume_cm3,
            'material_volume_cm3' => $item->material_volume_cm3,
            'support_volume_cm3' => $item->support_volume_cm3,
            'estimated_weight_g' => $item->estimated_weight_g,
            'support_weight_g' => $item->support_weight_g,
            'estimated_minutes' => $item->estimated_minutes,
            'estimated_cost' => $item->estimated_cost,
            'cost_breakdown' => $item->cost_breakdown,
        ];
    }

    /** Salin berkas model ke lokasi baru milik pesanan ulang. */
    private function copyFile(QuotationItem $item): string
    {
        $path = 'quotations/'.now()->format('Y-m').'/'.Str::uuid()
            .'.'.strtolower(pathinfo($item->file_name, PATHINFO_EXTENSION) ?: 'stl');

        Storage::disk('local')->copy($item->file_path, $path);

        return $path;
    }
}
