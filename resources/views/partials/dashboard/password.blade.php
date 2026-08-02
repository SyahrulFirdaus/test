@php
    $updateRoute = auth()->user()->isAdmin() ? 'admin.password.update' : 'dashboard.password.update';
@endphp

<div class="max-w-xl">
    <h2 class="text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">Ganti Password</h2>
    <p class="mt-2 text-sm text-ink-500">Masukkan kata sandi lama Anda, lalu tentukan kata sandi baru minimal 8 karakter.</p>

    <form method="POST" action="{{ route($updateRoute) }}" class="mt-8 space-y-5 rounded-2xl border border-ink-100 bg-white p-6 shadow-card sm:p-8">
        @csrf
        @method('PUT')

        <div>
            <label for="current_password" class="field-label">Password Lama <span class="text-brand-600">*</span></label>
            <input type="password" id="current_password" name="current_password" required autocomplete="current-password" class="field-input">
            @error('current_password') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="field-label">Password Baru <span class="text-brand-600">*</span></label>
            <input type="password" id="password" name="password" required autocomplete="new-password" class="field-input" placeholder="Minimal 8 karakter">
            @error('password') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="field-label">Konfirmasi Password Baru <span class="text-brand-600">*</span></label>
            <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" class="field-input">
        </div>

        <button type="submit" class="btn-primary w-full">Simpan Password Baru</button>
    </form>
</div>
