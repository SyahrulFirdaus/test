<?php

namespace App\Models;

use App\Models\Concerns\DescribesPrintJob;
use App\Support\AnalysisStatus;
use App\Support\QuotationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QuotationRequest extends Model
{
    use DescribesPrintJob;

    /** Status kelayakan cetak hasil analisis di browser. */
    public const ANALYSIS_READY = AnalysisStatus::READY;

    public const ANALYSIS_WARNING = AnalysisStatus::WARNING;

    public const ANALYSIS_NOT_PRINTABLE = AnalysisStatus::NOT_PRINTABLE;

    protected $fillable = [
        'user_id',
        'tracking_number',
        'name',
        'email',
        'whatsapp',
        'company',
        'quantity',
        'notes',
        'file_name',
        'file_path',
        'file_format',
        'file_size',
        'model_stats',
        'analysis_status',
        'analysis',
        'technology',
        'material',
        'finishing',
        'printer',
        'printer_name',
        'build_volume',
        'resolution',
        'layer_height_mm',
        'support_enabled',
        'support_type',
        'model_volume_cm3',
        'material_volume_cm3',
        'support_volume_cm3',
        'estimated_weight_g',
        'support_weight_g',
        'estimated_minutes',
        'estimated_cost',
        'cost_breakdown',
        'estimated_price',
        'estimated_finish',
        'production_photo',
        'result_photo',
        'status',
        'admin_note',

        'cancellation_reason',
        'status_before_cancellation',
        'cancellation_requested_at',
        'cancellation_resolved_at',
        'cancellation_admin_note',
    ];

    protected function casts(): array
    {
        return [
            'model_stats' => 'array',
            'analysis' => 'array',
            'build_volume' => 'array',
            'cost_breakdown' => 'array',
            'quantity' => 'integer',
            'file_size' => 'integer',
            'support_enabled' => 'boolean',
            'layer_height_mm' => 'decimal:3',
            'model_volume_cm3' => 'decimal:3',
            'material_volume_cm3' => 'decimal:3',
            'support_volume_cm3' => 'decimal:3',
            'estimated_weight_g' => 'decimal:2',
            'support_weight_g' => 'decimal:2',
            'estimated_minutes' => 'integer',
            'estimated_cost' => 'decimal:2',
            'estimated_price' => 'decimal:2',
            'estimated_finish' => 'date',
            'cancellation_requested_at' => 'datetime',
            'cancellation_resolved_at' => 'datetime',
        ];
    }

    /** Pemilik penawaran; kosong untuk penawaran lama yang dibuat tanpa akun. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Penawaran milik satu akun saja. */
    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    /** Penawaran yang menunggu keputusan pembatalan dari admin. */
    public function scopeAwaitingCancellation(Builder $query): Builder
    {
        return $query->where('status', QuotationStatus::CANCELLATION_REQUESTED);
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('created_at');
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return $query->when($status, fn (Builder $q) => $q->where('status', $status));
    }

    public function scopeTechnology(Builder $query, ?string $technology): Builder
    {
        return $query->when($technology, fn (Builder $q) => $q->where('technology', $technology));
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, function (Builder $q) use ($term) {
            $q->where(function (Builder $inner) use ($term) {
                $inner->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('company', 'like', "%{$term}%")
                    ->orWhere('file_name', 'like', "%{$term}%")
                    ->orWhere('tracking_number', 'like', "%{$term}%")
                    // Pencarian nama file harus menjangkau seluruh model dalam
                    // penawaran, bukan hanya model pertama yang tercatat di
                    // kolom ringkasan `file_name`.
                    ->orWhereHas('items', fn (Builder $item) => $item->where('file_name', 'like', "%{$term}%"));
            });
        });
    }

    /** Filter teknologi ikut memeriksa seluruh model di dalam penawaran. */
    public function scopeTechnologyAny(Builder $query, ?string $technology): Builder
    {
        return $query->when($technology, fn (Builder $q) => $q->where(function (Builder $inner) use ($technology) {
            $inner->where('technology', $technology)
                ->orWhereHas('items', fn (Builder $item) => $item->where('technology', $technology));
        }));
    }

    /** Seluruh model 3D yang termasuk dalam permintaan ini, urut sesuai unggahan. */
    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class)->orderBy('position');
    }

    /** Riwayat perubahan status, terbaru di atas. */
    public function histories(): HasMany
    {
        return $this->hasMany(QuotationHistory::class)->latest();
    }

    /** Riwayat urut maju, dipakai tabel riwayat pada halaman tracking. */
    public function timelineHistories(): HasMany
    {
        return $this->hasMany(QuotationHistory::class)->oldest();
    }

    /**
     * Catat perubahan status atau catatan baru ke riwayat.
     *
     * Riwayat selalu ditambah, tidak pernah ditimpa, sehingga jejak perkembangan
     * permintaan tidak hilang saat status berpindah.
     */
    public function recordHistory(string $status, ?string $note = null, ?string $createdBy = null): QuotationHistory
    {
        return $this->histories()->create([
            'status' => $status,
            'note' => $note,
            'created_by' => $createdBy,
        ]);
    }

    /** Label status permintaan sesuai config/printing.php. */
    public function getStatusLabelAttribute(): string
    {
        return QuotationStatus::label($this->status);
    }

    /** Langkah-langkah timeline beserta keadaannya terhadap status sekarang. */
    public function getTimelineAttribute(): array
    {
        // Saat pembatalan sedang diajukan, timeline tetap memperlihatkan tahap
        // terakhir yang benar-benar dijalani penawaran.
        return QuotationStatus::timeline(
            QuotationStatus::isCancellation($this->status)
                ? ($this->status_before_cancellation ?? $this->status)
                : $this->status
        );
    }

    /**
     * Isi penawaran masih boleh diubah pemiliknya.
     *
     * Hanya berlaku selama status masih "Menunggu Review". Begitu admin
     * memindahkannya ke "File Sedang Direview", seluruh datanya read only.
     */
    public function isEditable(): bool
    {
        return QuotationStatus::isEditable($this->status);
    }

    /** Pembatalan langsung — tidak perlu persetujuan admin. */
    public function canBeCancelledDirectly(): bool
    {
        return $this->isEditable();
    }

    /**
     * Pembatalan lewat pengajuan.
     *
     * Setelah berkas mulai direview, pembatalan tidak lagi langsung diproses:
     * user mengajukan, admin yang memutuskan.
     */
    public function canRequestCancellation(): bool
    {
        return ! $this->isEditable()
            && ! QuotationStatus::isCancellation($this->status)
            && $this->status !== QuotationStatus::COMPLETED;
    }

    /** Ada pengajuan pembatalan yang menunggu keputusan admin. */
    public function hasPendingCancellation(): bool
    {
        return $this->status === QuotationStatus::CANCELLATION_REQUESTED;
    }

    /** Penawaran sudah berhenti, baik selesai maupun dibatalkan. */
    public function isClosed(): bool
    {
        return QuotationStatus::isClosed($this->status);
    }

    /**
     * Selaraskan harga penawaran dengan harga tiap modelnya.
     *
     * Begitu admin menetapkan harga pada salah satu model, harga penawaran
     * menjadi penjumlahan harga seluruh model — model yang belum disesuaikan
     * memakai estimasi sistemnya sendiri. Selama belum ada satu pun harga
     * manual, kolom `estimated_price` dibiarkan kosong agar halaman tracking
     * tetap menampilkan angka sebagai "Estimasi Biaya Sistem".
     */
    public function refreshQuotedPrice(): void
    {
        $items = $this->items()->get();

        if ($items->isEmpty() || $items->every(fn (QuotationItem $item) => $item->estimated_price === null)) {
            $this->forceFill(['estimated_price' => null])->save();

            return;
        }

        $this->forceFill([
            'estimated_price' => round($items->sum(fn (QuotationItem $item) => (float) $item->display_price), 2),
        ])->save();
    }

    /**
     * Kolom ringkasan penawaran yang disusun dari seluruh modelnya.
     *
     * Berkas dan pilihan produksi mengikuti model pertama agar daftar admin,
     * pencarian, dan dokumen PDF tetap punya satu nilai yang mewakili; angka
     * estimasi dijumlahkan supaya total penawaran langsung terbaca tanpa
     * memuat seluruh itemnya.
     *
     * Menerima baik array atribut (saat penawaran baru dibentuk dan itemnya
     * belum tersimpan) maupun koleksi App\Models\QuotationItem (saat isinya
     * disunting dari dashboard), sehingga aturan ringkasannya hanya ada di
     * satu tempat.
     *
     * @param  iterable<int, array<string, mixed>|QuotationItem>  $items
     * @return array<string, mixed>
     */
    public static function summaryFrom(iterable $items): array
    {
        $items = collect($items)->values();
        $first = $items->first();
        $sum = fn (string $key) => $items->sum(fn ($item) => (float) $item[$key]);

        return [
            'file_name' => $first['file_name'],
            'file_path' => $first['file_path'],
            'file_format' => $first['file_format'],
            'file_size' => (int) $items->sum(fn ($item) => (int) $item['file_size']),

            'model_stats' => $first['model_stats'],
            'analysis_status' => AnalysisStatus::worst($items->pluck('analysis_status')->all()),
            'analysis' => $first['analysis'],

            'technology' => $first['technology'],
            'material' => $first['material'],
            'finishing' => $first['finishing'],

            // Mesin model pertama mewakili penawaran pada daftar admin & PDF;
            // rinciannya tetap dibaca per model.
            'printer' => $first['printer'],
            'printer_name' => $first['printer_name'],
            'build_volume' => $first['build_volume'],

            'quantity' => (int) $items->sum(fn ($item) => (int) $item['quantity']),
            'resolution' => $first['resolution'],
            'layer_height_mm' => $first['layer_height_mm'],
            'support_enabled' => $items->contains(fn ($item) => (bool) $item['support_enabled']),
            'support_type' => $first['support_type'],

            'model_volume_cm3' => round($sum('model_volume_cm3'), 3),
            'material_volume_cm3' => round($sum('material_volume_cm3'), 3),
            'support_volume_cm3' => round($sum('support_volume_cm3'), 3),
            'estimated_weight_g' => round($sum('estimated_weight_g'), 2),
            'support_weight_g' => round($sum('support_weight_g'), 2),
            'estimated_minutes' => (int) round($sum('estimated_minutes')),
            'estimated_cost' => round($sum('estimated_cost'), 2),

            // Rincian biaya penawaran adalah penjumlahan rincian tiap model,
            // sehingga tabelnya tetap berjumlah persis sama dengan totalnya.
            'cost_breakdown' => $items
                ->map(fn ($item) => (array) $item['cost_breakdown'])
                ->reduce(function (array $carry, array $breakdown) {
                    foreach ($breakdown as $component => $value) {
                        $carry[$component] = round(($carry[$component] ?? 0) + $value, 2);
                    }

                    return $carry;
                }, []),
        ];
    }

    /**
     * Hitung ulang kolom ringkasan dari model-model yang tersimpan sekarang.
     *
     * Dipanggil setiap kali isi penawaran berubah — model ditambah, dihapus,
     * atau pengaturannya disunting pemiliknya lewat dashboard.
     */
    public function refreshSummary(): void
    {
        $items = $this->items()->get();

        if ($items->isEmpty()) {
            return;
        }

        $this->forceFill(self::summaryFrom($items))->save();
        $this->refreshQuotedPrice();
    }

    /**
     * Ringkasan mesin yang dipakai, mis. "3 printer" atau nama mesinnya bila
     * seluruh model kebetulan dicetak pada mesin yang sama.
     */
    public function getPrinterSummaryAttribute(): string
    {
        $printers = $this->items->pluck('printer_label')->filter()->unique();

        if ($printers->count() <= 1) {
            return $printers->first() ?? $this->printer_label;
        }

        return $printers->count().' printer berbeda';
    }

    /** Jumlah model 3D di dalam penawaran ini. */
    public function getModelCountAttribute(): int
    {
        return $this->items_count ?? $this->items()->count();
    }

    /** Penawaran dengan lebih dari satu model ditampilkan sedikit berbeda. */
    public function hasMultipleModels(): bool
    {
        return $this->model_count > 1;
    }

    /**
     * Ringkasan nama berkas, mis. "gear.stl + 2 model lain".
     *
     * Kolom `file_name` hanya memuat model pertama, jadi daftar dan dokumen
     * memakai label ini agar model lainnya tidak seolah-olah hilang.
     */
    public function getFileSummaryAttribute(): string
    {
        $extra = $this->model_count - 1;

        return $extra > 0
            ? $this->file_name.' + '.$extra.' model lain'
            : (string) $this->file_name;
    }

    /**
     * Email disamarkan untuk halaman tracking publik: nomor tracking sudah cukup
     * sulit ditebak, tetapi alamat lengkap tidak perlu ikut terpampang.
     */
    public function getMaskedEmailAttribute(): ?string
    {
        if (blank($this->email) || ! str_contains($this->email, '@')) {
            return $this->email;
        }

        [$user, $domain] = explode('@', $this->email, 2);

        return Str::substr($user, 0, 2).str_repeat('*', max(3, Str::length($user) - 2)).'@'.$domain;
    }

    /** Nomor WhatsApp disamarkan, hanya empat angka terakhir yang ditampilkan. */
    public function getMaskedWhatsappAttribute(): ?string
    {
        if (blank($this->whatsapp)) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $this->whatsapp);

        return Str::length($digits) <= 4
            ? $digits
            : str_repeat('*', Str::length($digits) - 4).Str::substr($digits, -4);
    }

    /**
     * Nomor WhatsApp pemohon dalam format yang diterima wa.me.
     * Pelanggan umumnya mengisi format lokal (0812…), jadi awalan 0 diganti 62.
     */
    public function getWhatsappLinkAttribute(): ?string
    {
        if (blank($this->whatsapp)) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $this->whatsapp);

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (! str_starts_with($digits, '62')) {
            $digits = '62'.$digits;
        }

        return 'https://wa.me/'.$digits;
    }

    /** Hapus berkas seluruh model dan foto proses saat data permintaan dihapus. */
    protected static function booted(): void
    {
        static::deleting(function (self $quotation) {
            // Baris `quotation_items` terhapus lewat foreign key cascade yang
            // tidak memicu event model, jadi berkasnya dibereskan di sini.
            $quotation->items->each->delete();

            if ($quotation->fileExists()) {
                Storage::disk('local')->delete($quotation->file_path);
            }

            collect([$quotation->production_photo, $quotation->result_photo])
                ->filter()
                ->each(fn (string $path) => Storage::disk('public')->delete($path));
        });
    }
}
