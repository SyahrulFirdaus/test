{{--
    Data akun — isi langkah pertama pendaftaran.

    Nilainya diambil dari sesi pendaftaran ($account) supaya tetap terisi ketika
    pengguna menekan Kembali dari langkah berikutnya, dan dari old() ketika
    validasinya gagal.

    Data pengiriman diminta di sini agar formulir "Minta Penawaran" tidak perlu
    menanyakannya lagi setiap kali.
--}}
@php
    $value = fn (string $field) => old($field, $account[$field] ?? '');

    // Pelanggan perusahaan hanya mengisi kontak di sini; alamat pengirimannya
    // ditanyakan pada langkah Data Perusahaan berikutnya lengkap dengan wilayah
    // berjenjangnya, jadi tidak perlu ditanyakan dua kali.
    $isBusiness = ($customerType ?? \App\Support\CustomerType::PERSONAL) === \App\Support\CustomerType::BUSINESS;
@endphp

<div class="grid gap-5 sm:grid-cols-2">

    <div class="sm:col-span-2">
        <label for="name" class="field-label">Nama Lengkap <span class="text-brand-600">*</span></label>
        <input type="text" id="name" name="name" value="{{ $value('name') }}" required autofocus
               autocomplete="name" maxlength="120" class="field-input" placeholder="Nama sesuai identitas">
        @error('name') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="email" class="field-label">Email <span class="text-brand-600">*</span></label>
        <input type="email" id="email" name="email" value="{{ $value('email') }}" required
               autocomplete="email" maxlength="160" class="field-input" placeholder="nama@email.com">
        @error('email') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    <div @class(['sm:col-span-2' => $isBusiness])>
        <label for="phone" class="field-label">
            {{ $isBusiness ? 'Nomor WhatsApp' : 'Nomor Telepon' }} <span class="text-brand-600">*</span>
        </label>
        <input type="tel" id="phone" name="phone" value="{{ $value('phone') }}" required
               autocomplete="tel" maxlength="32" class="field-input" placeholder="0812 3456 7890">
        @error('phone') <p class="field-error">{{ $message }}</p> @enderror
    </div>

    @unless ($isBusiness)
        <div>
            <label for="city" class="field-label">Kota Asal <span class="text-brand-600">*</span></label>
            <input type="text" id="city" name="city" value="{{ $value('city') }}" required
                   autocomplete="address-level2" maxlength="120" class="field-input" placeholder="Bandung">
            @error('city') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="postal_code" class="field-label">Kode Pos <span class="text-brand-600">*</span></label>
            <input type="text" id="postal_code" name="postal_code" value="{{ $value('postal_code') }}" required
                   inputmode="numeric" autocomplete="postal-code" maxlength="12" class="field-input" placeholder="40123">
            @error('postal_code') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="sm:col-span-2">
            <label for="address" class="field-label">Alamat Lengkap <span class="text-brand-600">*</span></label>
            <textarea id="address" name="address" rows="3" required autocomplete="street-address" maxlength="500"
                      class="field-input" placeholder="Nama jalan, nomor, RT/RW, kelurahan, kecamatan">{{ $value('address') }}</textarea>
            @error('address') <p class="field-error">{{ $message }}</p> @enderror
        </div>
    @endunless

    <div class="sm:col-span-2 grid gap-5 sm:grid-cols-2">
        <div>
            <label for="password" class="field-label">Password <span class="text-brand-600">*</span></label>
            <input type="password" id="password" name="password" required autocomplete="new-password"
                   class="field-input" placeholder="Contoh: Nusama3D!">
            @error('password') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="field-label">Konfirmasi Password <span class="text-brand-600">*</span></label>
            <input type="password" id="password_confirmation" name="password_confirmation" required
                   autocomplete="new-password" class="field-input" placeholder="Ulangi kata sandi">
        </div>

        <p class="text-xs leading-relaxed text-ink-400 sm:col-span-2">
            {{ \App\Support\PasswordPolicy::hint() }}
        </p>
    </div>

</div>
