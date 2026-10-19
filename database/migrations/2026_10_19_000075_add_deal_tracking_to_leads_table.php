<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Export CSV Deal untuk Google Ads:
 *  - deal_at          : waktu lead ditandai Deal (waktu konversi). Diisi saat
 *                       status berubah menjadi Deal. Data lama diisi dari
 *                       Activity Log (perubahan status → "Deal" terakhir);
 *                       tanpa jejak di log dibiarkan kosong, tidak ditebak.
 *  - ads_exported_at  : pertama kali deal ini masuk file ekspor Google Ads.
 *  - ads_export_count : berapa kali deal ini ikut diekspor.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->timestamp('deal_at')->nullable()->after('deal_value');
            $table->timestamp('ads_exported_at')->nullable()->after('deal_at');
            $table->unsignedInteger('ads_export_count')->default(0)->after('ads_exported_at');

            $table->index(['status', 'deal_at']);
        });

        $morph = 'App\Models\Lead';

        DB::table('leads')->where('status', 'deal')->whereNull('deal_at')->orderBy('id')->each(function ($lead) use ($morph) {
            $log = DB::table('activity_logs')
                ->where('action', 'lead_update')
                ->where('subject_id', $lead->id)
                ->whereIn('subject_type', [$morph, 'lead'])
                ->orderByDesc('id')
                ->get(['new_values', 'created_at'])
                ->first(fn ($row) => (json_decode((string) $row->new_values, true)['status'] ?? null) === 'Deal');

            if ($log !== null) {
                DB::table('leads')->where('id', $lead->id)->update(['deal_at' => $log->created_at]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['status', 'deal_at']);
            $table->dropColumn(['deal_at', 'ads_exported_at', 'ads_export_count']);
        });
    }
};
