<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Katalog jenis aktivitas yang dicatat Activity Log.
 *
 * Kunci aktivitas disimpan apa adanya di basis data, sedangkan labelnya
 * dibaca dari sini saat ditampilkan — sehingga penamaan pada tabel Activity
 * Logs dapat dirapikan tanpa menyentuh baris log yang sudah tersimpan.
 *
 * Setiap aktivitas menempel pada satu modul. Pemetaannya dipakai pencatat
 * sebagai modul bawaan, jadi pemanggil cukup menyebut aktivitasnya.
 */
class ActivityAction
{
    /* ------------------------------------------------------------ account */

    public const REGISTER = 'register';

    public const LOGIN = 'login';

    public const LOGIN_FAILED = 'login_failed';

    public const LOGOUT = 'logout';

    public const PROFILE_UPDATE = 'profile_update';

    public const PASSWORD_UPDATE = 'password_update';

    public const PASSWORD_RESET = 'password_reset';

    public const ADDRESS_CREATE = 'address_create';

    public const ADDRESS_UPDATE = 'address_update';

    public const ADDRESS_DELETE = 'address_delete';

    public const COMPANY_UPDATE = 'company_update';

    /* --------------------------------------------------------- 3d models */

    public const MODEL_UPLOAD = 'model_upload';

    public const MODEL_DELETE = 'model_delete';

    public const SPEC_UPDATE = 'spec_update';

    /* --------------------------------------------------------- quotation */

    public const QUOTATION_CREATE = 'quotation_create';

    public const QUOTATION_VIEW = 'quotation_view';

    public const QUOTATION_STATUS_UPDATE = 'quotation_status_update';

    public const CANCELLATION_REQUEST = 'cancellation_request';

    public const CANCELLATION_APPROVE = 'cancellation_approve';

    public const CANCELLATION_REJECT = 'cancellation_reject';

    public const QUOTATION_CANCEL = 'quotation_cancel';

    public const QUOTATION_DELETE = 'quotation_delete';

    /* ----------------------------------------------------------- payment */

    public const PAYMENT_AWAITING = 'payment_awaiting';

    public const PAYMENT_PROOF_UPLOAD = 'payment_proof_upload';

    public const PAYMENT_APPROVE = 'payment_approve';

    public const PAYMENT_REJECT = 'payment_reject';

    public const PAYMENT_EXPIRED = 'payment_expired';

    public const PAYMENT_TERM_REQUEST = 'payment_term_request';

    public const PAYMENT_TERM_APPROVE = 'payment_term_approve';

    public const PAYMENT_TERM_REJECT = 'payment_term_reject';

    public const INSTALLMENT_PROOF_UPLOAD = 'installment_proof_upload';

    public const INSTALLMENT_APPROVE = 'installment_approve';

    public const INSTALLMENT_REJECT = 'installment_reject';

    /* ------------------------------------------------------------- admin */

    public const QUOTATION_PRICE_UPDATE = 'quotation_price_update';

    public const SETTING_UPDATE = 'setting_update';

    /**
     * Label dan modul bawaan tiap aktivitas.
     *
     * @return array<string, array{label: string, module: string}>
     */
    public static function all(): array
    {
        return [
            self::REGISTER => ['label' => 'Register', 'module' => ActivityModule::ACCOUNT],
            self::LOGIN => ['label' => 'Login', 'module' => ActivityModule::ACCOUNT],
            self::LOGIN_FAILED => ['label' => 'Login Gagal', 'module' => ActivityModule::ACCOUNT],
            self::LOGOUT => ['label' => 'Logout', 'module' => ActivityModule::ACCOUNT],
            self::PROFILE_UPDATE => ['label' => 'Update Profil', 'module' => ActivityModule::ACCOUNT],
            self::PASSWORD_UPDATE => ['label' => 'Ganti Password', 'module' => ActivityModule::ACCOUNT],
            self::PASSWORD_RESET => ['label' => 'Reset Password', 'module' => ActivityModule::ACCOUNT],
            self::ADDRESS_CREATE => ['label' => 'Tambah Alamat', 'module' => ActivityModule::ACCOUNT],
            self::ADDRESS_UPDATE => ['label' => 'Update Alamat', 'module' => ActivityModule::ACCOUNT],
            self::ADDRESS_DELETE => ['label' => 'Hapus Alamat', 'module' => ActivityModule::ACCOUNT],
            self::COMPANY_UPDATE => ['label' => 'Update Data Perusahaan', 'module' => ActivityModule::ACCOUNT],

            self::MODEL_UPLOAD => ['label' => 'Upload Model', 'module' => ActivityModule::MODELS],
            self::MODEL_DELETE => ['label' => 'Delete Model', 'module' => ActivityModule::MODELS],
            self::SPEC_UPDATE => ['label' => 'Edit Specification', 'module' => ActivityModule::MODELS],

            self::QUOTATION_CREATE => ['label' => 'Buat Penawaran', 'module' => ActivityModule::QUOTATION],
            self::QUOTATION_VIEW => ['label' => 'Lihat Penawaran', 'module' => ActivityModule::QUOTATION],
            self::QUOTATION_STATUS_UPDATE => ['label' => 'Ubah Status Penawaran', 'module' => ActivityModule::QUOTATION],
            self::CANCELLATION_REQUEST => ['label' => 'Ajukan Pembatalan', 'module' => ActivityModule::QUOTATION],
            self::CANCELLATION_APPROVE => ['label' => 'Setujui Pembatalan', 'module' => ActivityModule::QUOTATION],
            self::CANCELLATION_REJECT => ['label' => 'Tolak Pembatalan', 'module' => ActivityModule::QUOTATION],
            self::QUOTATION_CANCEL => ['label' => 'Batalkan Penawaran', 'module' => ActivityModule::QUOTATION],
            self::QUOTATION_DELETE => ['label' => 'Hapus Penawaran', 'module' => ActivityModule::QUOTATION],

            self::PAYMENT_AWAITING => ['label' => 'Menunggu Pembayaran', 'module' => ActivityModule::PAYMENT],
            self::PAYMENT_PROOF_UPLOAD => ['label' => 'Upload Bukti Pembayaran', 'module' => ActivityModule::PAYMENT],
            self::PAYMENT_APPROVE => ['label' => 'Terima Pembayaran', 'module' => ActivityModule::PAYMENT],
            self::PAYMENT_REJECT => ['label' => 'Tolak Pembayaran', 'module' => ActivityModule::PAYMENT],
            self::PAYMENT_EXPIRED => ['label' => 'Pembayaran Expired', 'module' => ActivityModule::PAYMENT],
            self::PAYMENT_TERM_REQUEST => ['label' => 'Ajukan Skema Pembayaran', 'module' => ActivityModule::PAYMENT],
            self::PAYMENT_TERM_APPROVE => ['label' => 'Setujui Skema Pembayaran', 'module' => ActivityModule::PAYMENT],
            self::PAYMENT_TERM_REJECT => ['label' => 'Tolak Skema Pembayaran', 'module' => ActivityModule::PAYMENT],
            self::INSTALLMENT_PROOF_UPLOAD => ['label' => 'Upload Bukti Termin', 'module' => ActivityModule::PAYMENT],
            self::INSTALLMENT_APPROVE => ['label' => 'Terima Pembayaran Termin', 'module' => ActivityModule::PAYMENT],
            self::INSTALLMENT_REJECT => ['label' => 'Tolak Pembayaran Termin', 'module' => ActivityModule::PAYMENT],

            self::QUOTATION_PRICE_UPDATE => ['label' => 'Ubah Estimasi Harga', 'module' => ActivityModule::ADMIN],
            self::SETTING_UPDATE => ['label' => 'Ubah Konfigurasi Sistem', 'module' => ActivityModule::ADMIN],
        ];
    }

    public static function label(?string $action): string
    {
        return self::all()[$action]['label'] ?? Str::title(str_replace('_', ' ', (string) $action));
    }

    /** Modul bawaan aktivitas; dipakai pencatat bila pemanggil tidak menyebutnya. */
    public static function module(?string $action): string
    {
        return self::all()[$action]['module'] ?? ActivityModule::ADMIN;
    }

    /** @return array<string, string> daftar kunci => label, untuk dropdown filter */
    public static function options(): array
    {
        return array_map(fn (array $action) => $action['label'], self::all());
    }
}
