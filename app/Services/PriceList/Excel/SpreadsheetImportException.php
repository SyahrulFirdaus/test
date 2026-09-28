<?php

namespace App\Services\PriceList\Excel;

use RuntimeException;

/**
 * Import dibatalkan di tengah transaksi karena hasil akhirnya tidak sah.
 *
 * Pesannya ditujukan kepada pengelola dan ditampilkan apa adanya. Karena
 * dilempar dari dalam `DB::transaction()`, tidak ada satu baris pun yang
 * tertinggal tersimpan.
 */
class SpreadsheetImportException extends RuntimeException {}
