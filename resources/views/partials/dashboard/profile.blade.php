@php
    $isAdmin = auth()->user()->isAdmin();
    $updateRoute = $isAdmin ? 'admin.profile.update' : 'dashboard.profile.update';
    $passwordRoute = $isAdmin ? 'admin.password.edit' : 'dashboard.password.edit';
@endphp

<div class="max-w-3xl">
    <h2 class="text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">Profil</h2>
    <p class="mt-2 text-sm text-ink-500">
        {{ $isAdmin
            ? 'Data akun administrator Anda.'
            : 'Data di bawah ini mengisi otomatis setiap penawaran baru yang Anda buat.' }}
    </p>

    <form method="POST" action="{{ route($updateRoute) }}" class="mt-8 rounded-2xl border border-ink-100 bg-white p-6 shadow-card sm:p-8">
        @csrf
        @method('PATCH')

        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="name" class="field-label">Nama Lengkap <span class="text-brand-600">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required maxlength="120" class="field-input">
                @error('name') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="field-label">Email <span class="text-brand-600">*</span></label>
                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="160" class="field-input">
                @error('email') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="phone" class="field-label">
                    Nomor Telepon @unless ($isAdmin) <span class="text-brand-600">*</span> @endunless
                </label>
                <input type="tel" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" maxlength="32" class="field-input" placeholder="0812 3456 7890">
                @error('phone') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="city" class="field-label">
                    Kota Asal @unless ($isAdmin) <span class="text-brand-600">*</span> @endunless
                </label>
                <input type="text" id="city" name="city" value="{{ old('city', $user->city) }}" maxlength="120" class="field-input">
                @error('city') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="postal_code" class="field-label">
                    Kode Pos @unless ($isAdmin) <span class="text-brand-600">*</span> @endunless
                </label>
                <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code', $user->postal_code) }}" inputmode="numeric" maxlength="12" class="field-input">
                @error('postal_code') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div class="sm:col-span-2">
                <label for="address" class="field-label">
                    Alamat Lengkap @unless ($isAdmin) <span class="text-brand-600">*</span> @endunless
                </label>
                <textarea id="address" name="address" rows="3" maxlength="500" class="field-input">{{ old('address', $user->address) }}</textarea>
                @error('address') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-7 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route($passwordRoute) }}" class="text-sm font-semibold text-brand-600 transition-colors hover:text-brand-700">
                Ganti Password &rarr;
            </a>
            <button type="submit" class="btn-primary">Simpan Perubahan</button>
        </div>
    </form>
</div>
