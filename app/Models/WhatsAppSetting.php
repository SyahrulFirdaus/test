<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Nomor WhatsApp pengirim NUSAMA3D beserta hasil pemeriksaan koneksinya.
 *
 * Hanya ada satu baris. Nomornya diatur lewat menu "Pengaturan WhatsApp"
 * supaya pergantian nomor tidak menuntut perubahan kode. Credential API
 * sengaja TIDAK disimpan di sini — lihat config/whatsapp.php.
 */
class WhatsAppSetting extends Model
{
    public const CONNECTED = 'connected';

    public const DISCONNECTED = 'disconnected';

    public const UNKNOWN = 'unknown';

    protected $table = 'whatsapp_settings';

    protected $fillable = [
        'phone_number',
        'phone_number_id',
        'connection_status',
        'connection_message',
        'connection_checked_at',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'connection_checked_at' => 'datetime',
        ];
    }

    /** Baris pengaturan satu-satunya; dibuat bila belum ada. */
    public static function current(): self
    {
        return static::query()->oldest('id')->first()
            ?? static::create(['connection_status' => self::UNKNOWN]);
    }

    public function hasSender(): bool
    {
        return filled($this->phone_number);
    }

    public function isConnected(): bool
    {
        return $this->connection_status === self::CONNECTED;
    }

    public function getConnectionLabelAttribute(): string
    {
        return match ($this->connection_status) {
            self::CONNECTED => 'Terhubung',
            self::DISCONNECTED => 'Tidak Terhubung',
            default => 'Belum Diperiksa',
        };
    }
}
