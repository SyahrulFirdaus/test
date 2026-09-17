<?php

namespace App\Support;

/**
 * Hak akses akun Admin, ditetapkan Superadmin per akun.
 *
 * Setiap hak akses berbentuk `<modul>.<aksi>`, mis. `quotation.view` dan
 * `quotation.delete`. Hak MELIHAT menu (`*.view`) sengaja dipisahkan dari hak
 * MELAKUKAN tindakan (`*.edit`, `*.delete`, …) sehingga Superadmin dapat
 * memberi akses yang sangat spesifik — mis. Admin Finance yang hanya melihat
 * penawaran tetapi boleh memverifikasi pembayaran.
 *
 * Inilah satu-satunya daftar hak akses. Menambah hak akses baru cukup dengan:
 *   1. menambah konstanta dan entrinya pada `modules()`;
 *   2. memasang middleware `admin.permission:<kunci>` pada route-nya;
 *   3. memakai `@can('<kunci>')` pada tombol/menu di Blade.
 * Baris tabel `permissions` dan Gate Laravel untuk setiap kunci dibuat otomatis
 * dari daftar ini (lihat App\Models\Permission::syncDefinitions() dan
 * AppServiceProvider), jadi tidak perlu diisi manual. Jangan menentukan hak
 * akses dari teks label — label hanya untuk tampilan.
 *
 * Hak akses hanya membatasi role Admin. Superadmin selalu memiliki seluruh
 * akses, dan role yang sudah ada tidak diubah sama sekali.
 */
class AdminPermission
{
    /* --------------------------------------------------------- Penawaran */

    public const QUOTATION_VIEW = 'quotation.view';

    public const QUOTATION_CREATE = 'quotation.create';

    public const QUOTATION_EDIT = 'quotation.edit';

    public const QUOTATION_DELETE = 'quotation.delete';

    public const QUOTATION_UPDATE_STATUS = 'quotation.update_status';

    /* ------------------------------------------------ Verifikasi Pembayaran */

    public const PAYMENT_VIEW = 'payment.view';

    public const PAYMENT_VERIFY = 'payment.verify';

    /* ------------------------------------------------------ Payment Term */

    public const PAYMENT_TERM_VIEW = 'payment_term.view';

    public const PAYMENT_TERM_EDIT = 'payment_term.edit';

    /* -------------------------------------------------------- Notifikasi */

    public const NOTIFICATION_VIEW = 'notification.view';

    /* -------------------------------------------------------------- User */

    public const USER_VIEW = 'user.view';

    public const USER_EDIT = 'user.edit';

    public const USER_DELETE = 'user.delete';

    /* ------------------------------------------------------------ Profil */

    public const PROFILE_EDIT = 'profile.edit';

    /*
     * Ganti Password milik akun sendiri. Kuncinya sengaja tidak memuat kata
     * "password": Activity Log membuang kolom yang namanya mengandung kata itu,
     * padahal hak akses ini perlu tercatat pada jejak perubahan hak akses.
     */
    public const PROFILE_SECURITY = 'profile.security';

    /**
     * Hak akses dikelompokkan per modul, urut tampil.
     *
     * `default` adalah posisi switch saat Superadmin membuat Admin baru.
     *
     * @return array<string, array{label: string, permissions: array<string, array{label: string, description: string, default: bool}>}>
     */
    public static function modules(): array
    {
        return [
            'quotation' => [
                'label' => 'Penawaran',
                'permissions' => [
                    self::QUOTATION_VIEW => [
                        'label' => 'Lihat',
                        'description' => 'Membuka menu Penawaran, melihat detail, dan mengunduh file model.',
                        'default' => false,
                    ],
                    self::QUOTATION_CREATE => [
                        'label' => 'Buat',
                        'description' => 'Membuat penawaran baru dari dashboard. Disiapkan untuk fitur pembuatan penawaran oleh Admin.',
                        'default' => false,
                    ],
                    self::QUOTATION_EDIT => [
                        'label' => 'Edit',
                        'description' => 'Menetapkan harga model (Form Perhitungan) dan catatan per model.',
                        'default' => false,
                    ],
                    self::QUOTATION_DELETE => [
                        'label' => 'Hapus',
                        'description' => 'Menghapus penawaran beserta berkas modelnya.',
                        'default' => false,
                    ],
                    self::QUOTATION_UPDATE_STATUS => [
                        'label' => 'Update Status',
                        'description' => 'Tindak lanjut status penawaran serta menyetujui/menolak pembatalan.',
                        'default' => false,
                    ],
                ],
            ],
            'payment' => [
                'label' => 'Verifikasi Pembayaran',
                'permissions' => [
                    self::PAYMENT_VIEW => [
                        'label' => 'Lihat',
                        'description' => 'Membuka menu Verifikasi Pembayaran dan melihat bukti transfer.',
                        'default' => false,
                    ],
                    self::PAYMENT_VERIFY => [
                        'label' => 'Verifikasi',
                        'description' => 'Menerima atau menolak pembayaran dan pembayaran termin.',
                        'default' => false,
                    ],
                ],
            ],
            'payment_term' => [
                'label' => 'Payment Term',
                'permissions' => [
                    self::PAYMENT_TERM_VIEW => [
                        'label' => 'Lihat',
                        'description' => 'Membuka menu Payment Term dan detail jadwal termin.',
                        'default' => false,
                    ],
                    self::PAYMENT_TERM_EDIT => [
                        'label' => 'Edit',
                        'description' => 'Menyetujui/menolak pengajuan, mengatur jadwal termin, dan pengaturan Payment Term.',
                        'default' => false,
                    ],
                ],
            ],
            'notification' => [
                'label' => 'Notifikasi',
                'permissions' => [
                    self::NOTIFICATION_VIEW => [
                        'label' => 'Lihat',
                        'description' => 'Halaman notifikasi dan ikon lonceng di header.',
                        'default' => false,
                    ],
                ],
            ],
            'user' => [
                'label' => 'User',
                'permissions' => [
                    self::USER_VIEW => [
                        'label' => 'Lihat',
                        'description' => 'Membuka menu User dan detail pelanggan beserta riwayat penawarannya.',
                        'default' => false,
                    ],
                    self::USER_EDIT => [
                        'label' => 'Edit',
                        'description' => 'Mengubah data pelanggan. Disiapkan untuk fitur pengelolaan User.',
                        'default' => false,
                    ],
                    self::USER_DELETE => [
                        'label' => 'Hapus',
                        'description' => 'Menghapus akun pelanggan. Disiapkan untuk fitur pengelolaan User.',
                        'default' => false,
                    ],
                ],
            ],
            'profile' => [
                'label' => 'Profil',
                'permissions' => [
                    self::PROFILE_EDIT => [
                        'label' => 'Edit',
                        'description' => 'Mengubah nama, email, dan data profil akunnya sendiri.',
                        'default' => true,
                    ],
                    self::PROFILE_SECURITY => [
                        'label' => 'Ganti Password',
                        'description' => 'Mengganti kata sandi akunnya sendiri.',
                        'default' => true,
                    ],
                ],
            ],
        ];
    }

    /**
     * Seluruh hak akses dalam satu daftar datar, urut tampil.
     *
     * @return array<string, array{module: string, module_label: string, action: string, label: string, description: string, default: bool}>
     */
    public static function definitions(): array
    {
        $definitions = [];

        foreach (self::modules() as $module => $group) {
            foreach ($group['permissions'] as $key => $permission) {
                $definitions[$key] = [
                    'module' => $module,
                    'module_label' => $group['label'],
                    'action' => substr($key, strlen($module) + 1),
                    ...$permission,
                ];
            }
        }

        return $definitions;
    }

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(self::definitions());
    }

    /** @return array<int, string> kunci yang menyala untuk Admin baru */
    public static function defaults(): array
    {
        return array_keys(array_filter(self::definitions(), fn (array $definition) => $definition['default']));
    }

    /** Label lengkap, mis. "Penawaran › Hapus". */
    public static function label(string $key): string
    {
        $definition = self::definitions()[$key] ?? null;

        return $definition === null ? $key : $definition['module_label'].' › '.$definition['label'];
    }

    /**
     * Rapikan kiriman switch sebelum disimpan.
     *
     * Kunci yang tidak dikenal dibuang, urutannya mengikuti katalog, dan
     * tindakan pada suatu modul otomatis membawa hak Lihat modul itu — Admin
     * tidak dapat mengedit penawaran yang menunya tidak boleh ia buka. Modul
     * tanpa hak Lihat (mis. Profil) dibiarkan apa adanya.
     *
     * @param  array<int, string>  $keys
     * @return array<int, string>
     */
    public static function normalize(array $keys): array
    {
        $definitions = self::definitions();
        $granted = array_flip(array_intersect(self::keys(), $keys));

        foreach (array_keys($granted) as $key) {
            $view = $definitions[$key]['module'].'.view';

            if (isset($definitions[$view])) {
                $granted[$view] = true;
            }
        }

        return array_values(array_filter(self::keys(), fn (string $key) => isset($granted[$key])));
    }

    /**
     * Posisi setiap switch untuk jejak aktivitas, mis. `quotation.view => ON`.
     *
     * @param  array<int, string>  $granted
     * @return array<string, string>
     */
    public static function snapshot(array $granted): array
    {
        return collect(self::keys())
            ->mapWithKeys(fn (string $key) => [$key => in_array($key, $granted, true) ? 'ON' : 'OFF'])
            ->all();
    }
}
