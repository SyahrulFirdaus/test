<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Support\ActivityAction;
use App\Support\ActivityModule;
use App\Support\ActivityStatus;
use App\Support\ActorType;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Menu "Activity Logs" pada dashboard admin.
 *
 * Halaman ini murni membaca. Jejak audit tidak boleh disunting maupun dihapus
 * lewat antarmuka — begitu satu baris dapat diubah, seluruh gunanya sebagai
 * bukti ikut hilang.
 *
 * Pencatatannya sendiri dilakukan App\Services\ActivityLogger dari masing-
 * masing alur kerja, bukan dari sini.
 */
class ActivityLogController extends Controller
{
    /** Jumlah baris per halaman; log tumbuh cepat, jadi sengaja tidak besar. */
    private const PER_PAGE = 20;

    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        $logs = ActivityLog::query()
            ->with('user:id,name,email,customer_type,role')
            ->searchUser($filters['user'])
            ->searchActivity($filters['activity'])
            ->ofUserType($filters['type'])
            ->ofModule($filters['module'])
            ->ofStatus($filters['status'])
            ->betweenDates($filters['from'], $filters['to'])
            ->latestFirst()
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('superadmin.activity-logs.index', [
            'logs' => $logs,
            'filters' => $filters,
            'accountTypes' => ActorType::options(),
            'modules' => ActivityModule::options(),
            'statuses' => ActivityStatus::options(),
            'activities' => ActivityAction::options(),
            'summary' => $this->summary(),
        ]);
    }

    /**
     * Rincian satu aktivitas beserta perbandingan data sebelum dan sesudahnya.
     */
    public function show(ActivityLog $activityLog): View
    {
        return view('superadmin.activity-logs.show', [
            'log' => $activityLog->load('user'),
            'changes' => $activityLog->recordedChanges(),
        ]);
    }

    /**
     * Nilai penyaring yang sedang aktif.
     *
     * Nilai di luar daftar yang dikenal dijatuhkan menjadi kosong, sehingga
     * alamat yang diketik sembarangan tetap menghasilkan daftar yang benar
     * alih-alih daftar kosong tanpa penjelasan.
     *
     * @return array<string, ?string>
     */
    private function filters(Request $request): array
    {
        $type = $request->query('type');
        $module = $request->query('module');
        $status = $request->query('status');

        return [
            'user' => $this->text($request->query('user')),
            'activity' => $this->text($request->query('activity')),
            'type' => ActorType::exists($type) ? $type : null,
            'module' => ActivityModule::exists($module) ? $module : null,
            'status' => ActivityStatus::exists($status) ? $status : null,
            'from' => $this->text($request->query('from')),
            'to' => $this->text($request->query('to')),
        ];
    }

    private function text(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : mb_substr($value, 0, 190);
    }

    /**
     * Ringkasan di atas tabel.
     *
     * Angkanya sengaja tidak mengikuti penyaring: yang dicari admin di sini
     * adalah keadaan sistem hari ini, bukan jumlah baris hasil pencarian yang
     * sudah terbaca dari paginasinya.
     *
     * @return array<string, int>
     */
    private function summary(): array
    {
        return [
            'total' => ActivityLog::count(),
            'today' => ActivityLog::whereDate('created_at', today())->count(),
            'failed_today' => ActivityLog::whereDate('created_at', today())
                ->where('status', ActivityStatus::FAILED)
                ->count(),
            'actors_today' => ActivityLog::whereDate('created_at', today())
                ->distinct()
                ->count('user_id'),
        ];
    }
}
