<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Kolom `reference` berganti nama menjadi `tracking_number` supaya hanya ada
     * satu nomor yang dipakai pelanggan, PDF, QR code, dan dashboard admin —
     * bukan dua identitas berbeda untuk satu permintaan.
     */
    public function up(): void
    {
        Schema::table('quotation_requests', function (Blueprint $table) {
            $table->renameColumn('reference', 'tracking_number');
        });

        Schema::table('quotation_requests', function (Blueprint $table) {
            // Harga & tanggal selesai versi admin, terpisah dari estimasi otomatis
            // (`estimated_cost` / `estimated_minutes`) yang dihitung sistem.
            $table->decimal('estimated_price', 14, 2)->nullable()->after('estimated_cost');
            $table->date('estimated_finish')->nullable()->after('estimated_price');
            $table->string('production_photo')->nullable()->after('estimated_finish');
            $table->string('result_photo')->nullable()->after('production_photo');
        });

        // Nomor lama berformat QR-YYYYMM-XXXX, diselaraskan ke QTN-YYYYMMDD-XXXXXX.
        DB::table('quotation_requests')->orderBy('id')->each(function ($row) {
            $date = $row->created_at ? date('Ymd', strtotime((string) $row->created_at)) : date('Ymd');

            DB::table('quotation_requests')
                ->where('id', $row->id)
                ->update(['tracking_number' => 'QTN-'.$date.'-'.strtoupper(Str::random(6))]);
        });

        // Status lama dipetakan ke tahap awal alur yang baru.
        DB::table('quotation_requests')->whereIn('status', ['new', 'reviewed'])->update(['status' => 'received']);
        DB::table('quotation_requests')->whereIn('status', ['won', 'closed'])->update(['status' => 'completed']);

        Schema::create('quotation_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_request_id')->constrained()->cascadeOnDelete();
            $table->string('status', 40);
            $table->text('note')->nullable();
            $table->string('created_by')->nullable()->comment('Nama admin, atau null bila dibuat sistem');
            $table->timestamps();

            $table->index(['quotation_request_id', 'created_at']);
        });

        // Riwayat awal untuk permintaan yang sudah ada sebelum fitur ini dibuat.
        DB::table('quotation_requests')->orderBy('id')->each(function ($row) {
            DB::table('quotation_histories')->insert([
                'quotation_request_id' => $row->id,
                'status' => 'received',
                'note' => 'Permintaan penawaran berhasil dikirim.',
                'created_by' => null,
                'created_at' => $row->created_at,
                'updated_at' => $row->created_at,
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_histories');

        Schema::table('quotation_requests', function (Blueprint $table) {
            $table->dropColumn(['estimated_price', 'estimated_finish', 'production_photo', 'result_photo']);
        });

        Schema::table('quotation_requests', function (Blueprint $table) {
            $table->renameColumn('tracking_number', 'reference');
        });
    }
};
