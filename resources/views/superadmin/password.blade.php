@extends('layouts.dashboard')

{{-- Ganti Password Superadmin. Setelah berhasil, sesinya diakhiri dan
     pengguna langsung dialihkan ke /superadmin/login — lihat
     App\Http\Controllers\Auth\PasswordController::update(). --}}

@section('title', 'Ganti Password')

@section('content')
    <div class="max-w-xl">
        <h2 class="text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">Ganti Password</h2>
        <p class="mt-2 text-sm text-ink-500">
            Masukkan password lama, lalu tentukan password baru. Setelah disimpan Anda akan keluar otomatis
            dan perlu login kembali dengan password baru.
        </p>

        <form method="POST" action="{{ route('superadmin.password.update') }}"
              class="mt-8 space-y-5 rounded-2xl border border-ink-100 bg-white p-6 shadow-card sm:p-8">
            @csrf
            @method('PUT')

            @foreach ([
                ['id' => 'current_password', 'label' => 'Password Lama', 'autocomplete' => 'current-password', 'hint' => null],
                ['id' => 'password', 'label' => 'Password Baru', 'autocomplete' => 'new-password', 'hint' => \App\Support\PasswordPolicy::hint()],
                ['id' => 'password_confirmation', 'label' => 'Konfirmasi Password Baru', 'autocomplete' => 'new-password', 'hint' => null],
            ] as $field)
                <div>
                    <label for="{{ $field['id'] }}" class="field-label">{{ $field['label'] }} <span class="text-brand-600">*</span></label>
                    <x-password-field :id="$field['id']" :name="$field['id']" required :autocomplete="$field['autocomplete']"
                                      :state-icon="true"
                                      class="field-input mt-0 {{ $errors->has($field['id']) ? 'border-brand-600' : '' }}" />
                    @if ($field['hint'])
                        <p class="mt-1.5 text-xs leading-relaxed text-ink-400">{{ $field['hint'] }}</p>
                    @endif
                    @error($field['id']) <p class="field-error">{{ $message }}</p> @enderror
                </div>
            @endforeach

            <div class="flex flex-col-reverse gap-3 border-t border-ink-100 pt-5 sm:flex-row sm:justify-end">
                <a href="{{ route('superadmin.dashboard') }}" class="btn-outline justify-center">Batal</a>
                <button type="submit" class="btn-primary justify-center">Ganti Password</button>
            </div>
        </form>
    </div>
@endsection
