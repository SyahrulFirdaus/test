<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAddressRequest;
use App\Models\Address;
use App\Models\District;
use App\Models\Province;
use App\Models\Regency;
use App\Models\Village;
use App\Services\ActivityLogger;
use App\Services\AddressBook;
use App\Support\ActivityAction;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Buku alamat pengiriman pelanggan.
 *
 * Satu akun boleh menyimpan beberapa alamat dan menandai satu sebagai alamat
 * utama; alamat utama itulah yang terpilih lebih dulu ketika meminta penawaran.
 *
 * Seluruh aksi di sini hanya menyentuh alamat milik akun yang sedang masuk —
 * pemeriksaannya lewat `authorize()` pada tiap alamat yang dibuka.
 */
class AddressController extends Controller
{
    public function __construct(
        private readonly AddressBook $addresses,
        private readonly ActivityLogger $activity,
    ) {}

    public function index(): View
    {
        $addresses = auth()->user()
            ->addresses()
            ->with(Address::REGION_RELATIONS)
            ->get();

        return view('dashboard.addresses.index', [
            'addresses' => $addresses,
            // Alamat hasil pemindahan akun lama belum tentu lengkap wilayahnya;
            // jumlahnya dipakai untuk mengingatkan pemiliknya di halaman daftar.
            'incomplete' => $addresses->reject->isComplete()->count(),
        ]);
    }

    public function create(): View
    {
        $address = new Address;

        return view('dashboard.addresses.form', [
            'address' => $address,
            ...$this->regionOptions($address),
        ]);
    }

    public function store(StoreAddressRequest $request): RedirectResponse
    {
        $address = $this->addresses->create(auth()->user(), $request->validated());

        $this->activity->log(
            action: ActivityAction::ADDRESS_CREATE,
            description: 'Menambahkan alamat pengiriman '.$address->label.'.',
            subject: $address,
            new: $this->snapshot($address),
            subjectLabel: $address->label,
        );

        return redirect()
            ->route('dashboard.addresses.index')
            ->with('status', 'Alamat berhasil ditambahkan.');
    }

    public function edit(Address $address): View
    {
        $this->authorizeOwnership($address);

        return view('dashboard.addresses.form', [
            'address' => $address,
            ...$this->regionOptions($address),
        ]);
    }

    public function update(StoreAddressRequest $request, Address $address): RedirectResponse
    {
        $this->authorizeOwnership($address);

        $before = $this->snapshot($address);

        $this->addresses->update($address, $request->validated());

        $this->activity->logChanges(
            action: ActivityAction::ADDRESS_UPDATE,
            before: $before,
            after: $this->snapshot($address->refresh()),
            description: 'Memperbarui alamat pengiriman '.$address->label.'.',
            subject: $address,
            subjectLabel: $address->label,
        );

        return redirect()
            ->route('dashboard.addresses.index')
            ->with('status', 'Alamat berhasil diperbarui.');
    }

    public function destroy(Address $address): RedirectResponse
    {
        $this->authorizeOwnership($address);

        $removed = $this->snapshot($address);
        $label = $address->label;

        $this->addresses->delete($address);

        $this->activity->log(
            action: ActivityAction::ADDRESS_DELETE,
            description: 'Menghapus alamat pengiriman '.$label.'.',
            old: $removed,
            subjectLabel: $label,
        );

        return redirect()
            ->route('dashboard.addresses.index')
            ->with('status', 'Alamat berhasil dihapus.');
    }

    /**
     * Isi alamat untuk jejak audit.
     *
     * Wilayahnya diambil sebagai nama, bukan id, supaya perbandingan
     * Before/After pada halaman Activity Logs terbaca tanpa perlu menelusuri
     * tabel wilayah.
     *
     * @return array<string, mixed>
     */
    private function snapshot(Address $address): array
    {
        return [
            'label' => $address->label,
            'recipient_name' => $address->recipient_name,
            'recipient_phone' => $address->recipient_phone,
            'province' => $address->province?->name,
            'regency' => $address->regency?->name,
            'district' => $address->district?->name,
            'village' => $address->village?->name,
            'postal_code' => $address->postal_code,
            'detail' => $address->detail,
            'note' => $address->note,
            'is_default' => (bool) $address->is_default,
        ];
    }

    /** Tandai satu alamat sebagai alamat utama. */
    public function makeDefault(Address $address): RedirectResponse
    {
        $this->authorizeOwnership($address);

        $this->addresses->makeDefault($address);

        return redirect()
            ->route('dashboard.addresses.index')
            ->with('status', 'Alamat utama diperbarui.');
    }

    /**
     * Pilihan wilayah untuk formulir.
     *
     * Hanya daftar provinsi yang ikut bersama halaman. Tiga tingkat di
     * bawahnya diambil menyusul mengikuti pilihan sebelumnya, karena daftar
     * kelurahan se-Indonesia berjumlah puluhan ribu baris dan tidak mungkin
     * dikirim sekaligus.
     *
     * Pada penyuntingan alamat yang sudah terisi, tingkat yang sudah punya
     * nilai ikut dimuat lebih dulu agar dropdown-nya langsung terisi tanpa
     * menunggu permintaan tambahan.
     *
     * @return array<string, mixed>
     */
    private function regionOptions(Address $address): array
    {
        return [
            'provinces' => Province::ordered()->get(['id', 'name']),
            'regencies' => $address->province_id
                ? Regency::where('province_id', $address->province_id)->ordered()->get(['id', 'name'])
                : collect(),
            'districts' => $address->regency_id
                ? District::where('regency_id', $address->regency_id)->ordered()->get(['id', 'name'])
                : collect(),
            'villages' => $address->district_id
                ? Village::where('district_id', $address->district_id)->ordered()->get(['id', 'name'])
                : collect(),
        ];
    }

    private function authorizeOwnership(Address $address): void
    {
        abort_if($address->user_id !== auth()->id(), 404);
    }
}
