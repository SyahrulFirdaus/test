<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportMaterialExcelRequest;
use App\Models\PrintTechnology;
use App\Services\ActivityLogger;
use App\Services\PriceList\Excel\ExcelRuntime;
use App\Services\PriceList\Excel\MaterialExporter;
use App\Services\PriceList\Excel\MaterialImporter;
use App\Services\PriceList\Excel\MaterialImportReader;
use App\Services\PriceList\Excel\MaterialImportResult;
use App\Support\ActivityAction;
use App\Support\ActivityModule;
use App\Support\PriceListPage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Import & Export Excel material Price List.
 *
 * Pelengkap CRUD material yang sudah ada, bukan penggantinya: Tambah, Ubah,
 * Hapus, pencarian, paginasi, pengelompokan mesin, dan metode harga tidak
 * disentuh sama sekali. Yang ditambahkan hanyalah cara kedua memasukkan data
 * yang sama, untuk pekerjaan massal.
 *
 * Controller ini sengaja tipis. Seluruh pekerjaan Excel — menyusun berkas,
 * membaca, memeriksa, dan menyimpan — berada di App\Services\PriceList\Excel.
 *
 * Alurnya dua langkah dan tidak dapat dipotong:
 *
 *   1. `preview()` membaca berkas, memeriksa seluruhnya, lalu MENAMPILKAN
 *      hasilnya. Tidak ada satu baris pun yang disimpan.
 *   2. `store()` menyimpan baris sah dari pratinjau itu, dalam satu transaksi.
 *
 * Hasil pemeriksaan dititipkan di sesi di antara keduanya, jadi berkasnya tidak
 * perlu diunggah dua kali dan tidak ada salinan berkas yang tertinggal di
 * server.
 */
class MaterialExcelController extends Controller
{
    /** Kunci sesi tempat hasil pratinjau dititipkan, per teknologi. */
    private const SESSION_PREFIX = 'price-list.material-import.';

    public function __construct(
        private readonly MaterialExporter $exporter,
        private readonly MaterialImportReader $reader,
        private readonly MaterialImporter $importer,
        private readonly ActivityLogger $activity,
    ) {}

    /* ============================================================ unduh === */

    public function export(PrintTechnology $technology): Response|RedirectResponse
    {
        if ($blocked = $this->guardExcelRuntime($technology)) {
            return $blocked;
        }

        $count = $technology->materials()->count();

        $this->activity->log(
            action: ActivityAction::PRICE_LIST_EXPORT,
            description: 'Meng-export '.$count.' material '.$technology->tabLabel().' ke Excel.',
            subject: $technology,
            module: ActivityModule::SUPERADMIN,
            subjectLabel: $technology->tabLabel(),
        );

        return $this->exporter->export($technology);
    }

    public function template(PrintTechnology $technology): Response|RedirectResponse
    {
        if ($blocked = $this->guardExcelRuntime($technology)) {
            return $blocked;
        }

        $this->logDownload($technology, 'Template');

        return $this->exporter->template($technology);
    }

    public function example(PrintTechnology $technology): Response|RedirectResponse
    {
        if ($blocked = $this->guardExcelRuntime($technology)) {
            return $blocked;
        }

        $this->logDownload($technology, 'Contoh');

        return $this->exporter->example($technology);
    }

    /* ========================================================= pratinjau === */

    /**
     * Baca berkas, periksa seluruhnya, lalu tampilkan hasilnya.
     *
     * Tidak menyentuh basis data sama sekali — bahkan ketika seluruh barisnya
     * sah. Yang memutuskan tetap pengelola, pada halaman berikutnya.
     */
    public function preview(ImportMaterialExcelRequest $request, PrintTechnology $technology): RedirectResponse|View
    {
        $file = $request->file('file');
        $result = $this->reader->read($file->getRealPath(), $technology, $file->getClientOriginalName());

        if ($result->isFatal()) {
            return redirect()
                ->to(PriceListPage::technologyUrl($technology))
                ->withErrors(['file' => $result->fatal[0]]);
        }

        $request->session()->put($this->sessionKey($technology), $result->toArray());

        return view('superadmin.price-list.material.import-preview', [
            'technology' => $technology,
            'result' => $result,
        ]);
    }

    /* ========================================================== simpan === */

    /**
     * Simpan baris sah dari pratinjau yang baru saja ditampilkan.
     *
     * Yang disimpan dibaca dari sesi, BUKAN dari kiriman formulir: tabel
     * pratinjau hanya menampilkan, jadi isinya tidak dapat disunting di
     * peramban lalu dikirim sebagai data lain.
     */
    public function store(Request $request, PrintTechnology $technology): RedirectResponse
    {
        $validated = $request->validate([
            'on_duplicate' => ['required', 'in:'.MaterialImporter::ON_DUPLICATE_SKIP.','.MaterialImporter::ON_DUPLICATE_UPDATE],
        ], [
            'on_duplicate.required' => 'Tentukan dulu perlakuan untuk material yang sudah ada.',
        ]);

        $stored = $request->session()->pull($this->sessionKey($technology));

        if (! is_array($stored)) {
            return redirect()
                ->to(PriceListPage::technologyUrl($technology))
                ->withErrors(['file' => 'Pratinjau import sudah tidak berlaku. Unggah ulang filenya.']);
        }

        $result = MaterialImportResult::fromArray($stored);

        if (! $result->hasAnythingToImport()) {
            return redirect()
                ->to(PriceListPage::technologyUrl($technology))
                ->withErrors(['file' => 'Tidak ada baris yang dapat diimpor dari file itu.']);
        }

        $summary = $this->importer->import($technology, $result->valid(), $validated['on_duplicate']);

        $this->activity->log(
            action: ActivityAction::PRICE_LIST_IMPORT,
            description: 'Meng-import material '.$technology->tabLabel().' dari "'.$result->fileName.'": '
                .$summary['created'].' ditambahkan, '.$summary['updated'].' diperbarui, '
                .$summary['skipped'].' dilewati, '.count($result->invalid()).' baris bermasalah tidak diimpor.',
            subject: $technology,
            new: [
                'file' => $result->fileName,
                'total_baris' => $result->total(),
                'ditambahkan' => $summary['created'],
                'diperbarui' => $summary['updated'],
                'dilewati' => $summary['skipped'],
                'bermasalah' => count($result->invalid()),
                'perubahan' => $summary['changes'],
            ],
            module: ActivityModule::SUPERADMIN,
            subjectLabel: $technology->tabLabel(),
        );

        return redirect()
            ->to(PriceListPage::technologyUrl($technology))
            ->with('status', 'Import selesai: '.$summary['created'].' material ditambahkan, '
                .$summary['updated'].' diperbarui, '.$summary['skipped'].' dilewati.');
    }

    /** Batalkan pratinjau tanpa menyimpan apa pun. */
    public function cancel(Request $request, PrintTechnology $technology): RedirectResponse
    {
        $request->session()->forget($this->sessionKey($technology));

        return redirect()
            ->to(PriceListPage::technologyUrl($technology))
            ->with('status', 'Import dibatalkan. Tidak ada data yang berubah.');
    }

    /* =========================================================== utilitas === */

    /**
     * Tahan unduhan bila PHP-nya belum dapat menulis .xlsx.
     *
     * Tanpa ini yang sampai ke pengelola adalah halaman galat 500 berisi
     * `Class "ZipArchive" not found`. Import sudah dijaga pembacanya sendiri;
     * ketiga unduhan dijaga di sini karena penulisnya menuntut hal yang sama.
     */
    private function guardExcelRuntime(PrintTechnology $technology): ?RedirectResponse
    {
        if (ExcelRuntime::available()) {
            return null;
        }

        return redirect()
            ->to(PriceListPage::technologyUrl($technology))
            ->withErrors(['file' => ExcelRuntime::unavailableMessage()]);
    }

    private function logDownload(PrintTechnology $technology, string $what): void
    {
        $this->activity->log(
            action: ActivityAction::PRICE_LIST_DOWNLOAD,
            description: 'Mengunduh '.$what.' Excel material '.$technology->tabLabel().'.',
            subject: $technology,
            module: ActivityModule::SUPERADMIN,
            subjectLabel: $technology->tabLabel(),
        );
    }

    private function sessionKey(PrintTechnology $technology): string
    {
        return self::SESSION_PREFIX.$technology->getKey();
    }
}
