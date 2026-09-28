<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportMaterialExcelRequest;
use App\Services\ActivityLogger;
use App\Services\PriceList\Excel\DatasetExporter;
use App\Services\PriceList\Excel\ExcelRuntime;
use App\Services\PriceList\Excel\MaterialImportResult;
use App\Services\PriceList\Excel\SpreadsheetDataset;
use App\Services\PriceList\Excel\SpreadsheetImportException;
use App\Support\ActivityAction;
use App\Support\ActivityModule;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Import & Export Excel halaman Price List selain material.
 *
 * Alur dan perilakunya sama persis dengan MaterialExcelController — unggahan
 * yang sama (ImportMaterialExcelRequest), pratinjau yang dititipkan di sesi,
 * penyimpanan dalam satu transaksi setelah dikonfirmasi, Skip/Update untuk
 * data kembar, dan catatan Activity Log yang sama. Turunannya hanya menyebut
 * dataset dan halaman asalnya.
 */
abstract class DatasetExcelController extends Controller
{
    public function __construct(
        protected readonly DatasetExporter $exporter,
        protected readonly ActivityLogger $activity,
    ) {}

    abstract protected function dataset(): SpreadsheetDataset;

    /** Halaman tabel tempat tombol Excel berada. */
    abstract protected function indexUrl(): string;

    /** Awalan nama route Excel, mis. "superadmin.price-list.technologies.excel". */
    abstract protected function routePrefix(): string;

    /** Judul grup sidebar pada layout Price List. */
    abstract protected function group(): string;

    /* ============================================================ unduh === */

    public function export(): Response|RedirectResponse
    {
        if ($blocked = $this->guardExcelRuntime()) {
            return $blocked;
        }

        $dataset = $this->dataset();

        $this->activity->log(
            action: ActivityAction::PRICE_LIST_EXPORT,
            description: 'Meng-export '.$dataset->exportCount().' '.$dataset->noun().' ('.$dataset->title().') ke Excel.',
            module: ActivityModule::SUPERADMIN,
            subjectLabel: $dataset->title(),
        );

        return $this->exporter->export($dataset);
    }

    public function template(): Response|RedirectResponse
    {
        if ($blocked = $this->guardExcelRuntime()) {
            return $blocked;
        }

        $this->logDownload('Template');

        return $this->exporter->template($this->dataset());
    }

    public function example(): Response|RedirectResponse
    {
        if ($blocked = $this->guardExcelRuntime()) {
            return $blocked;
        }

        $this->logDownload('Contoh');

        return $this->exporter->example($this->dataset());
    }

    /* ========================================================= pratinjau === */

    /** Baca dan periksa seluruh berkas; tidak ada yang disimpan. */
    public function preview(ImportMaterialExcelRequest $request): RedirectResponse|View
    {
        $dataset = $this->dataset();
        $file = $request->file('file');
        $result = $dataset->read($file->getRealPath(), $file->getClientOriginalName());

        if ($result->isFatal()) {
            return redirect()->to($this->indexUrl())->withErrors(['file' => $result->fatal[0]]);
        }

        $request->session()->put($this->sessionKey(), $result->toArray());

        return view('superadmin.price-list.excel.import-preview', [
            'dataset' => $dataset,
            'result' => $result,
            'routePrefix' => $this->routePrefix(),
            'backUrl' => $this->indexUrl(),
            'group' => $this->group(),
        ]);
    }

    /* ========================================================== simpan === */

    /** Simpan baris sah dari pratinjau — dibaca dari sesi, bukan dari formulir. */
    public function store(Request $request): RedirectResponse
    {
        $dataset = $this->dataset();

        $validated = $request->validate([
            'on_duplicate' => ['required', 'in:'.SpreadsheetDataset::ON_DUPLICATE_SKIP.','.SpreadsheetDataset::ON_DUPLICATE_UPDATE],
        ], [
            'on_duplicate.required' => 'Tentukan dulu perlakuan untuk '.$dataset->noun().' yang sudah ada.',
        ]);

        $stored = $request->session()->pull($this->sessionKey());

        if (! is_array($stored)) {
            return redirect()->to($this->indexUrl())
                ->withErrors(['file' => 'Pratinjau import sudah tidak berlaku. Unggah ulang filenya.']);
        }

        $result = MaterialImportResult::fromArray($stored);

        if (! $result->hasAnythingToImport()) {
            return redirect()->to($this->indexUrl())
                ->withErrors(['file' => 'Tidak ada baris yang dapat diimpor dari file itu.']);
        }

        try {
            $summary = $dataset->import($result->valid(), $validated['on_duplicate']);
        } catch (SpreadsheetImportException $exception) {
            $this->activity->logFailure(
                action: ActivityAction::PRICE_LIST_IMPORT,
                description: 'Import '.$dataset->noun().' dari "'.$result->fileName.'" dibatalkan: '.$exception->getMessage(),
                module: ActivityModule::SUPERADMIN,
            );

            return redirect()->to($this->indexUrl())->withErrors(['file' => $exception->getMessage()]);
        }

        $this->activity->log(
            action: ActivityAction::PRICE_LIST_IMPORT,
            description: 'Meng-import '.$dataset->noun().' dari "'.$result->fileName.'": '
                .$summary['created'].' ditambahkan, '.$summary['updated'].' diperbarui, '
                .$summary['skipped'].' dilewati, '.count($result->invalid()).' baris bermasalah tidak diimpor.',
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
            subjectLabel: $dataset->title(),
        );

        return redirect()->to($this->indexUrl())
            ->with('status', 'Import selesai: '.$summary['created'].' '.$dataset->noun().' ditambahkan, '
                .$summary['updated'].' diperbarui, '.$summary['skipped'].' dilewati.');
    }

    public function cancel(Request $request): RedirectResponse
    {
        $request->session()->forget($this->sessionKey());

        return redirect()->to($this->indexUrl())
            ->with('status', 'Import dibatalkan. Tidak ada data yang berubah.');
    }

    /* =========================================================== utilitas === */

    private function guardExcelRuntime(): ?RedirectResponse
    {
        return ExcelRuntime::available()
            ? null
            : redirect()->to($this->indexUrl())->withErrors(['file' => ExcelRuntime::unavailableMessage()]);
    }

    private function logDownload(string $what): void
    {
        $dataset = $this->dataset();

        $this->activity->log(
            action: ActivityAction::PRICE_LIST_DOWNLOAD,
            description: 'Mengunduh '.$what.' Excel '.$dataset->title().'.',
            module: ActivityModule::SUPERADMIN,
            subjectLabel: $dataset->title(),
        );
    }

    private function sessionKey(): string
    {
        return 'price-list.'.$this->dataset()->key().'-import';
    }
}
