<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SLA Industries menjadi "SLA", dengan metode penentuan harga PER MATERIAL.
 *
 * 1. `print_materials.pricing_method` — `automatic` (Kalkulator Otomatis,
 *    rumus Harga Jual yang sama dengan FDM) atau `manual` (Kalkulator Manual,
 *    kuotasi JLC SLA Industries yang sudah ada). Material yang sudah ada diberi
 *    `manual`: itulah mekanisme yang berlaku bagi SLA Industries selama ini,
 *    dan bagi teknologi lain kolom ini tidak dibaca sama sekali.
 *
 * 2. `print_technologies.is_active` — teknologi nonaktif tidak ditampilkan di
 *    Edit Specification maupun Price List, tetapi barisnya tetap ada supaya
 *    penawaran lama yang menunjuk kodenya tetap terbaca.
 *
 * 3. Teknologi SLA lama (kode `SLA`) digabung ke teknologi SLA Industries
 *    (kode `SLAI`, kini bernama "SLA"): materialnya dipindahkan dengan
 *    `pricing_method = automatic` — harganya tetap dihitung otomatis seperti
 *    sebelumnya — mesinnya ikut pindah, lalu teknologi lamanya dinonaktifkan.
 *    Kode `SLAI` sendiri TIDAK diubah karena sudah tersimpan pada
 *    `quotation_items.technology`.
 */
return new class extends Migration
{
    private const OLD_CODE = 'SLA';

    private const NEW_CODE = 'SLAI';

    public function up(): void
    {
        Schema::table('print_materials', function (Blueprint $table) {
            $table->string('pricing_method', 20)->default('manual')->after('remark');
        });

        Schema::table('print_technologies', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('allows_hollow');
        });

        $old = DB::table('print_technologies')->where('code', self::OLD_CODE)->first();
        $new = DB::table('print_technologies')->where('code', self::NEW_CODE)->first();

        if ($new === null) {
            return;
        }

        // Nama tampilannya. Hanya ditimpa bila masih nilai bawaan, supaya
        // suntingan Superadmin tidak hilang.
        $rename = [];

        if ($new->name === 'SLA Industries') {
            $rename['name'] = 'SLA';
        }

        if ($new->family === 'Industrial Resin') {
            $rename['family'] = 'Resin';
        }

        if (str_starts_with((string) $new->description, 'Part resin industri yang dikerjakan mitra produksi kami.')) {
            $rename['description'] = 'Resin fotopolimer dikeraskan lapis demi lapis oleh sinar UV. '
                .'Harga material tertentu dihitung otomatis, sedangkan material lainnya ditetapkan tim setelah penawaran masuk.';
        }

        if ($old === null) {
            if ($rename !== []) {
                DB::table('print_technologies')->where('id', $new->id)->update($rename + ['updated_at' => now()]);
            }

            return;
        }

        // Kalkulator Otomatis membaca throughput dan tarif mesin teknologinya.
        // SLA Industries tidak pernah mengisinya (nol), jadi parameter produksi
        // SLA lama dipakai — hanya yang masih nol.
        foreach (['throughput_cm3_per_hour', 'setup_hours', 'setup_fee', 'machine_rate_per_hour'] as $column) {
            if ((float) $new->{$column} <= 0 && (float) $old->{$column} > 0) {
                $rename[$column] = $old->{$column};
            }
        }

        // Parameter geometri bawaan SLA Industries hanya pengisi (partnya dahulu
        // tidak dihitung dari berat). Resin SLA mengeras padat, jadi nilai SLA
        // lama yang dipakai selama nilainya belum pernah disunting.
        if ((float) $new->shell_ratio === 0.25 && (float) $new->default_infill === 0.2 && $new->infill_note === null) {
            $rename['shell_ratio'] = $old->shell_ratio;
            $rename['default_infill'] = $old->default_infill;
            $rename['infill_note'] = $old->infill_note;
        }

        DB::table('print_technologies')->where('id', $new->id)->update($rename + ['updated_at' => now()]);

        DB::transaction(function () use ($old, $new) {
            $taken = DB::table('print_materials')
                ->where('print_technology_id', $new->id)
                ->get(['material', 'machine_cost_id'])
                ->map(fn ($row) => $row->material.'|'.$row->machine_cost_id)
                ->all();

            foreach (DB::table('print_materials')->where('print_technology_id', $old->id)->get() as $row) {
                $name = $row->material;

                // Nama yang sudah dipakai material SLA Industries pada mesin
                // yang sama diberi akhiran, bukan ditinggalkan atau ditimpa.
                if (in_array($name.'|'.$row->machine_cost_id, $taken, true)) {
                    $name .= ' (SLA)';
                }

                $taken[] = $name.'|'.$row->machine_cost_id;

                DB::table('print_materials')->where('id', $row->id)->update([
                    'print_technology_id' => $new->id,
                    'material' => $name,
                    'pricing_method' => 'automatic',
                    'updated_at' => now(),
                ]);
            }

            DB::table('machine_costs')
                ->where('print_technology_id', $old->id)
                ->update(['print_technology_id' => $new->id]);

            DB::table('print_technologies')->where('id', $old->id)->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        $old = DB::table('print_technologies')->where('code', self::OLD_CODE)->first();
        $new = DB::table('print_technologies')->where('code', self::NEW_CODE)->first();

        if ($old !== null && $new !== null) {
            DB::table('print_materials')
                ->where('print_technology_id', $new->id)
                ->where('pricing_method', 'automatic')
                ->update(['print_technology_id' => $old->id]);
        }

        if ($new !== null && $new->name === 'SLA') {
            DB::table('print_technologies')->where('id', $new->id)->update(['name' => 'SLA Industries']);
        }

        Schema::table('print_technologies', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('print_materials', function (Blueprint $table) {
            $table->dropColumn('pricing_method');
        });
    }
};
