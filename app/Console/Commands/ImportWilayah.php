<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Memuat daftar wilayah Indonesia dari berkas CSV berkode berjenjang.
 *
 * Berkas sumbernya berisi dua kolom, `kode` dan `nama`, dengan kode resmi
 * Kemendagri yang tingkatannya terbaca dari banyaknya titik:
 *
 *   32              provinsi
 *   32.77           kabupaten/kota
 *   32.77.01        kecamatan
 *   32.77.01.1001   kelurahan/desa
 *
 * Induk sebuah wilayah adalah kodenya sendiri dikurangi satu ruas terakhir,
 * jadi tidak ada kolom relasi terpisah yang perlu dicocokkan.
 *
 * Baris yang sudah ada diperbarui, bukan dihapus lalu dibuat ulang, supaya
 * alamat pelanggan yang menunjuk id wilayah tidak pernah terputus ketika
 * daftarnya diperbarui ke keputusan menteri berikutnya.
 */
class ImportWilayah extends Command
{
    protected $signature = 'wilayah:import
                            {file? : Berkas CSV berisi kolom kode,nama}';

    protected $description = 'Impor provinsi, kabupaten/kota, kecamatan, dan kelurahan dari berkas CSV';

    /** Banyaknya baris per sekali tulis ke basis data. */
    private const CHUNK = 500;

    /** Tingkat kode => nama tabel tujuannya. */
    private const TABLES = [
        1 => ['table' => 'provinces', 'parent' => null],
        2 => ['table' => 'regencies', 'parent' => 'province_id'],
        3 => ['table' => 'districts', 'parent' => 'regency_id'],
        4 => ['table' => 'villages', 'parent' => 'district_id'],
    ];

    public function handle(): int
    {
        $path = $this->argument('file') ?: database_path('data/wilayah.csv');

        if (! is_file($path)) {
            $this->error("Berkas tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);

        if ($header === false || count($header) < 2) {
            fclose($handle);
            $this->error('Berkas CSV harus memuat kolom kode dan nama.');

            return self::FAILURE;
        }

        // Dikelompokkan per tingkat lebih dulu: induk harus sudah tersimpan
        // sebelum anaknya, kalau tidak foreign key-nya ditolak.
        $rows = [1 => [], 2 => [], 3 => [], 4 => []];
        $now = now();

        while (($line = fgetcsv($handle)) !== false) {
            $code = trim((string) ($line[0] ?? ''));
            $name = trim((string) ($line[1] ?? ''));

            if ($code === '' || $name === '') {
                continue;
            }

            $parts = explode('.', $code);
            $level = count($parts);

            if (! isset(self::TABLES[$level])) {
                continue;
            }

            $row = [
                'id' => (int) str_replace('.', '', $code),
                'code' => $code,
                'name' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($parent = self::TABLES[$level]['parent']) {
                array_pop($parts);
                $row[$parent] = (int) implode('', $parts);
            }

            $rows[$level][] = $row;
        }

        fclose($handle);

        DB::transaction(function () use ($rows) {
            foreach (self::TABLES as $level => $target) {
                $this->write($target['table'], $rows[$level], $target['parent']);
            }
        });

        $this->newLine();
        $this->info(sprintf(
            'Selesai: %s provinsi, %s kabupaten/kota, %s kecamatan, %s kelurahan/desa.',
            number_format(count($rows[1]), 0, ',', '.'),
            number_format(count($rows[2]), 0, ',', '.'),
            number_format(count($rows[3]), 0, ',', '.'),
            number_format(count($rows[4]), 0, ',', '.'),
        ));

        return self::SUCCESS;
    }

    /**
     * Tulis satu tingkat wilayah secara bertahap.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function write(string $table, array $rows, ?string $parent): void
    {
        if ($rows === []) {
            return;
        }

        $updatable = array_values(array_filter(['code', 'name', $parent, 'updated_at']));
        $bar = $this->output->createProgressBar(count($rows));
        $bar->setMessage($table);

        foreach (array_chunk($rows, self::CHUNK) as $chunk) {
            DB::table($table)->upsert($chunk, ['id'], $updatable);
            $bar->advance(count($chunk));
        }

        $bar->finish();
        $this->newLine();
    }
}
