<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PrintTechnology;
use App\Models\QuotationItem;
use App\Models\QuotationRequest;
use App\Notifications\CancellationDecided;
use App\Notifications\QuotationStatusUpdated;
use App\Services\ActivityLogger;
use App\Services\SellingPriceEstimator;
use App\Services\PaymentFlow;
use App\Support\ActivityAction;
use App\Support\ActivityModule;
use App\Support\QuotationStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuotationRequestController extends Controller
{
    public function __construct(
        private readonly PaymentFlow $payments,
        private readonly ActivityLogger $activity,
    ) {}

    /** Daftar seluruh permintaan penawaran beserta filter dan ringkasannya. */
    public function index(Request $request): View
    {
        $quotations = QuotationRequest::query()
            ->status($request->query('status'))
            ->technologyAny($request->query('technology'))
            ->search($request->query('q'))
            ->withCount('items')
            ->latestFirst()
            ->paginate(15)
            ->withQueryString();

        return view('admin.quotations.index', [
            'quotations' => $quotations,
            'statuses' => QuotationStatus::options(),
            'pendingCancellations' => QuotationRequest::query()->awaitingCancellation()->count(),
            'technologies' => PrintTechnology::codes(),
            'filters' => [
                'status' => $request->query('status'),
                'technology' => $request->query('technology'),
                'q' => $request->query('q'),
            ],
            'summary' => [
                'total' => QuotationRequest::count(),
                'new' => QuotationRequest::where('status', QuotationStatus::first())->count(),
                'ready' => QuotationItem::where('analysis_status', QuotationRequest::ANALYSIS_READY)->count(),
                'value' => QuotationRequest::sum('estimated_cost'),
                'models' => QuotationItem::count(),
            ],
        ]);
    }

    public function show(QuotationRequest $quotation, SellingPriceEstimator $sellingPrice): View
    {
        // Pilihan status dihitung dari status yang tersimpan, bukan dari
        // tampilan sebelumnya — menyegarkan halaman selalu mengembalikan tahap
        // yang sama beserta kuncinya.
        $selectable = $this->selectableStatuses($quotation);

        return view('admin.quotations.show', [
            // Rincian Harga Jual tiap model: dibaca dari perhitungan yang sudah
            // tersimpan, jadi membuka halaman ini tidak menghitung ulang apa pun
            // dan tidak dapat menggeser harga yang sudah ditawarkan.
            'sellingPrice' => $sellingPrice->forQuotation($quotation),
            'quotation' => $quotation->load('timelineHistories', 'items', 'user'),
            // Status pembatalan tidak ikut ke dropdown: perpindahannya diatur
            // tombol Setujui / Tolak Pembatalan.
            'statusChoices' => QuotationStatus::manualChoices($quotation->status, $quotation->status_before_cancellation),
            // Tahap berikutnya tidak pernah terpilih sendiri: yang terpilih
            // adalah tahap yang sedang berjalan, kecuali admin baru saja
            // mengirim pilihan yang sah tetapi gagal validasi di bagian lain.
            'statusDefault' => in_array(old('status'), $selectable, true) ? old('status') : ($selectable[0] ?? null),
            'statusNext' => QuotationStatus::next($quotation->status, $quotation->status_before_cancellation),
        ]);
    }

    /**
     * Tahap yang sah dipasang pada penawaran ini saat request datang.
     *
     * Satu tempat untuk dropdown maupun validasi, supaya keduanya tidak pernah
     * berbeda pendapat tentang tahap mana yang terbuka.
     *
     * @return array<int, string>
     */
    private function selectableStatuses(QuotationRequest $quotation): array
    {
        return QuotationStatus::selectableFrom($quotation->status, $quotation->status_before_cancellation);
    }

    /**
     * Perbarui status, estimasi, catatan, dan foto proses.
     *
     * Setiap perubahan status maupun catatan baru dicatat sebagai baris riwayat
     * tersendiri — riwayat lama tidak pernah ditimpa, sehingga jejak
     * perkembangan permintaan tetap utuh di halaman tracking pelanggan.
     */
    public function update(Request $request, QuotationRequest $quotation): RedirectResponse
    {
        // Status berjalan selangkah demi selangkah: hanya tahap yang sedang
        // berjalan dan tepat satu tahap sesudahnya yang sah. Pemeriksaannya ada
        // di sini, bukan hanya pada dropdown, sehingga kiriman yang dibuat
        // sendiri ke endpoint ini pun tidak dapat melompati tahap.
        $selectable = $this->selectableStatuses($quotation);

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in($selectable)],
            'estimated_finish' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:2000'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
            'production_photo' => ['nullable', 'image', 'max:4096'],
            'result_photo' => ['nullable', 'image', 'max:4096'],
        ], [
            'status.in' => $this->stepMessage($quotation),
            'production_photo.image' => 'Foto proses produksi harus berupa gambar.',
            'result_photo.image' => 'Foto hasil akhir harus berupa gambar.',
        ]);

        $statusChanged = $quotation->status !== $validated['status'];
        $note = filled($validated['note'] ?? null) ? trim($validated['note']) : null;

        $attributes = [
            'status' => $validated['status'],
            // Harga penawaran mengikuti estimasi sistem sejak permintaan
            // dikirim; admin tidak lagi mengisinya sendiri.
            'estimated_finish' => $validated['estimated_finish'] ?? null,
            'admin_note' => $validated['admin_note'] ?? null,
        ];

        foreach (['production_photo', 'result_photo'] as $field) {
            if ($request->hasFile($field)) {
                // Foto proses memang ditampilkan ke pelanggan, jadi disimpan pada
                // disk publik — berbeda dari berkas model yang tetap privat.
                if ($quotation->{$field}) {
                    Storage::disk('public')->delete($quotation->{$field});
                }

                $attributes[$field] = $request->file($field)->store('quotations/'.now()->format('Y-m'), 'public');
            }
        }

        // Keadaan sebelum penyimpanan direkam untuk perbandingan Before/After
        // pada jejak audit — termasuk status, harga, dan catatan admin.
        $before = [
            'status' => QuotationStatus::label($quotation->status),
            'estimated_price' => $quotation->estimated_price,
            'estimated_finish' => $quotation->estimated_finish?->format('Y-m-d'),
            'admin_note' => $quotation->admin_note,
        ];

        $quotation->update($attributes);

        $this->activity->logChanges(
            action: ActivityAction::QUOTATION_STATUS_UPDATE,
            before: $before,
            after: [
                'status' => QuotationStatus::label($quotation->status),
                'estimated_price' => $quotation->estimated_price,
                'estimated_finish' => $quotation->estimated_finish?->format('Y-m-d'),
                'admin_note' => $quotation->admin_note,
            ],
            description: $statusChanged
                ? 'Mengubah status penawaran '.$quotation->tracking_number.' menjadi '.QuotationStatus::label($validated['status']).'.'
                : 'Memperbarui data penawaran '.$quotation->tracking_number.'.',
            subject: $quotation,
            actor: $request->user(),
        );

        // Berpindah ke "Menunggu Pembayaran" membuka jendela pembayaran 24 jam
        // sekaligus membersihkan bukti dari percobaan sebelumnya.
        if ($statusChanged && $validated['status'] === QuotationStatus::AWAITING_PAYMENT) {
            $this->payments->open($quotation);
        }

        if ($statusChanged || $note !== null) {
            $quotation->recordHistory(
                $validated['status'],
                $note ?? 'Status diperbarui menjadi '.QuotationStatus::label($validated['status']).'.',
                $request->user()?->name,
            );
        }

        // Setiap perpindahan status langsung tampil di dashboard pemiliknya
        // lewat ikon lonceng dan halaman Notifikasi.
        if ($statusChanged) {
            $quotation->user?->notify(new QuotationStatusUpdated($quotation, $validated['status'], $note));
        }

        return back()->with('status', $statusChanged
            ? 'Status diperbarui menjadi '.QuotationStatus::label($validated['status']).' dan tercatat di riwayat.'
            : 'Perubahan berhasil disimpan.');
    }

    /** Alasan sebuah status ditolak, disebutkan lengkap dengan tahap yang terbuka. */
    private function stepMessage(QuotationRequest $quotation): string
    {
        $next = QuotationStatus::next($quotation->status, $quotation->status_before_cancellation);

        if ($next === null) {
            return 'Penawaran sudah berada pada tahap terakhir, statusnya tidak dapat dimajukan lagi.';
        }

        return 'Status hanya dapat maju satu tahap. Dari "'.QuotationStatus::label($quotation->status)
            .'", tahap yang dapat dipilih berikutnya adalah "'.QuotationStatus::label($next).'".';
    }

    /**
     * Setujui permintaan pembatalan.
     *
     * Penawaran berhenti di sini: statusnya menjadi "Pembatalan Disetujui" dan
     * tidak lagi berjalan maju.
     */
    public function approveCancellation(Request $request, QuotationRequest $quotation): RedirectResponse
    {
        if (! $quotation->hasPendingCancellation()) {
            return back()->with('error', 'Tidak ada permintaan pembatalan yang menunggu keputusan pada penawaran ini.');
        }

        $note = $this->decisionNote($request);
        $statusBefore = $quotation->status;

        $quotation->update([
            'status' => QuotationStatus::CANCELLATION_APPROVED,
            'cancellation_resolved_at' => now(),
            'cancellation_admin_note' => $note,
        ]);

        $this->activity->log(
            action: ActivityAction::CANCELLATION_APPROVE,
            description: 'Menyetujui pembatalan penawaran '.$quotation->tracking_number
                .($note ? ' dengan catatan: '.$note : '.'),
            subject: $quotation,
            old: ['status' => QuotationStatus::label($statusBefore)],
            new: [
                'status' => QuotationStatus::label(QuotationStatus::CANCELLATION_APPROVED),
                'admin_note' => $note,
            ],
            actor: $request->user(),
        );

        $quotation->recordHistory(
            QuotationStatus::CANCELLATION_APPROVED,
            $note ?? 'Permintaan pembatalan disetujui.',
            $request->user()?->name,
        );

        $quotation->user?->notify(new CancellationDecided($quotation, true, $note));

        return back()->with('status', 'Permintaan pembatalan disetujui dan pelanggan sudah diberi tahu.');
    }

    /**
     * Tolak permintaan pembatalan.
     *
     * Penawaran dikembalikan ke tahap yang sedang dijalaninya sebelum
     * pengajuan, sehingga prosesnya dapat diteruskan seperti semula.
     */
    public function rejectCancellation(Request $request, QuotationRequest $quotation): RedirectResponse
    {
        if (! $quotation->hasPendingCancellation()) {
            return back()->with('error', 'Tidak ada permintaan pembatalan yang menunggu keputusan pada penawaran ini.');
        }

        $note = $this->decisionNote($request);
        $restored = $quotation->status_before_cancellation ?: QuotationStatus::REVIEWING;
        $statusBefore = $quotation->status;

        $quotation->update([
            'status' => $restored,
            'status_before_cancellation' => null,
            'cancellation_resolved_at' => now(),
            'cancellation_admin_note' => $note,
        ]);

        // Riwayat mencatat penolakannya lebih dulu, lalu tahap yang dilanjutkan,
        // supaya jejaknya terbaca utuh di halaman tracking.
        $quotation->recordHistory(
            QuotationStatus::CANCELLATION_REJECTED,
            $note ?? 'Permintaan pembatalan ditolak, penawaran diteruskan.',
            $request->user()?->name,
        );

        $quotation->recordHistory(
            $restored,
            'Penawaran dilanjutkan pada tahap '.QuotationStatus::label($restored).'.',
            $request->user()?->name,
        );

        $this->activity->log(
            action: ActivityAction::CANCELLATION_REJECT,
            description: 'Menolak pembatalan penawaran '.$quotation->tracking_number
                .', dilanjutkan pada tahap '.QuotationStatus::label($restored).'.',
            subject: $quotation,
            old: ['status' => QuotationStatus::label($statusBefore)],
            new: [
                'status' => QuotationStatus::label($restored),
                'admin_note' => $note,
            ],
            actor: $request->user(),
        );

        $quotation->user?->notify(new CancellationDecided($quotation, false, $note));

        return back()->with('status', 'Permintaan pembatalan ditolak. Penawaran dilanjutkan pada tahap '.QuotationStatus::label($restored).'.');
    }

    /** Catatan admin yang menyertai keputusan pembatalan. */
    private function decisionNote(Request $request): ?string
    {
        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        return filled($validated['note'] ?? null) ? trim($validated['note']) : null;
    }

    /** Unduh berkas model pertama milik permintaan. */
    public function download(QuotationRequest $quotation): StreamedResponse|RedirectResponse
    {
        if (! $quotation->fileExists()) {
            return back()->with('error', 'Berkas model tidak ditemukan di penyimpanan.');
        }

        return Storage::disk('local')->download($quotation->file_path, $quotation->file_name);
    }

    /** Unduh berkas salah satu model di dalam permintaan. */
    public function downloadItem(QuotationRequest $quotation, QuotationItem $item): StreamedResponse|RedirectResponse
    {
        if (! $item->fileExists()) {
            return back()->with('error', "Berkas model {$item->file_name} tidak ditemukan di penyimpanan.");
        }

        return Storage::disk('local')->download($item->file_path, $item->file_name);
    }

    /**
     * Perbarui estimasi harga dan catatan untuk satu model.
     *
     * Perubahan di sini hanya menyentuh model yang bersangkutan; estimasi model
     * lain dalam penawaran yang sama tidak ikut berubah. Bila harga salah satu
     * model disesuaikan, harga penawaran ikut dihitung ulang dari penjumlahan
     * harga seluruh modelnya.
     */
    public function updateItem(Request $request, QuotationRequest $quotation, QuotationItem $item): RedirectResponse
    {
        // Harga model TIDAK lagi dapat disunting admin: seluruh harga
        // penawaran mengikuti estimasi sistem. Kiriman `estimated_price`
        // sengaja tidak divalidasi maupun dipakai, jadi formulir lama yang
        // masih mengirimnya pun tidak dapat menggeser harga.
        $validated = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $before = $item->only(['admin_note']);

        $item->update([
            'admin_note' => filled($validated['admin_note'] ?? null) ? trim($validated['admin_note']) : null,
        ]);

        $this->activity->logChanges(
            action: ActivityAction::QUOTATION_ITEM_NOTE_UPDATE,
            before: $before,
            after: $item->only(['admin_note']),
            description: 'Mengubah catatan model '.$item->file_name.' pada penawaran '.$quotation->tracking_number.'.',
            subject: $item,
            actor: $request->user(),
            module: ActivityModule::ADMIN,
            subjectLabel: $item->file_name,
        );

        return back()->with('status', "Catatan model {$item->file_name} berhasil disimpan.");
    }

    public function destroy(Request $request, QuotationRequest $quotation): RedirectResponse
    {
        // Isi penawaran direkam sebelum barisnya hilang. Penghapusan justru
        // aktivitas yang paling perlu meninggalkan jejak, karena datanya
        // sendiri tidak lagi dapat diperiksa setelahnya.
        $removed = [
            'tracking_number' => $quotation->tracking_number,
            'customer' => $quotation->name,
            'email' => $quotation->email,
            'status' => QuotationStatus::label($quotation->status),
            'model_count' => $quotation->items()->count(),
            'estimated_price' => $quotation->estimated_price,
        ];

        $trackingNumber = $quotation->tracking_number;

        // Berkas dan foto ikut terhapus lewat event `deleting` pada model,
        // riwayat ikut terhapus lewat foreign key cascade.
        $quotation->delete();

        $this->activity->log(
            action: ActivityAction::QUOTATION_DELETE,
            description: 'Menghapus penawaran '.$trackingNumber.' beserta seluruh berkasnya.',
            old: $removed,
            actor: $request->user(),
            module: ActivityModule::ADMIN,
            subjectLabel: $trackingNumber,
        );

        return redirect()
            ->route('admin.quotations.index')
            ->with('status', 'Permintaan penawaran berhasil dihapus.');
    }
}
