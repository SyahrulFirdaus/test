@extends('layouts.dashboard')

@php
    $isEdit = $admin->exists;
@endphp

@section('title', $isEdit ? 'Edit Admin' : 'Tambah Admin')

@section('content')
    <a href="{{ route('superadmin.admins.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-ink-500 transition-colors hover:text-brand-600">
        &larr; Kembali ke Akun Admin
    </a>

    <h1 class="mt-5 font-display text-2xl font-bold tracking-tight text-ink-900">
        {{ $isEdit ? 'Edit Akun Admin' : 'Tambah Akun Admin' }}
    </h1>
    <p class="mt-1 text-sm text-ink-500">
        {{ $isEdit
            ? 'Kosongkan kolom kata sandi bila tidak ingin menggantinya.'
            : 'Akun ini langsung dapat dipakai masuk ke Dashboard Admin setelah disimpan.' }}
    </p>

    <form method="POST"
          action="{{ $isEdit ? route('superadmin.admins.update', $admin) : route('superadmin.admins.store') }}"
          class="mt-6 max-w-xl rounded-2xl border border-ink-100 bg-white p-6 shadow-card sm:p-7">
        @csrf
        @if ($isEdit) @method('PATCH') @endif

        <div>
            <label for="name" class="field-label">Nama</label>
            <input type="text" id="name" name="name" required maxlength="120"
                   value="{{ old('name', $admin->name) }}" class="field-input" placeholder="Nama admin">
            @error('name') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="mt-5">
            <label for="email" class="field-label">Email</label>
            <input type="email" id="email" name="email" required maxlength="190"
                   value="{{ old('email', $admin->email) }}" class="field-input" placeholder="admin@nusama3d.com">
            @error('email') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="mt-5">
            <label for="password" class="field-label">
                Password
                @if ($isEdit) <span class="text-ink-300">(kosongkan bila tidak diganti)</span> @endif
            </label>
            <x-password-field id="password" name="password" autocomplete="new-password"
                              :required="! $isEdit"
                              class="field-input mt-0" placeholder="Kata sandi admin" />
            <p class="mt-1.5 text-xs text-ink-400">{{ \App\Support\PasswordPolicy::hint() }}</p>
            @error('password') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="mt-5">
            <label for="password_confirmation" class="field-label">Konfirmasi Password</label>
            <x-password-field id="password_confirmation" name="password_confirmation" autocomplete="new-password"
                              :required="! $isEdit"
                              class="field-input mt-0" placeholder="Ulangi kata sandi" />
        </div>

        <div class="mt-5 rounded-xl border border-ink-100 p-4">
            <label class="flex items-start gap-3 text-sm text-ink-700">
                <input type="checkbox" name="is_active" value="1"
                       @checked(old('is_active', $admin->exists ? $admin->isActive() : true))
                       class="mt-0.5 h-4 w-4 rounded border-ink-300 text-brand-600 focus:ring-brand-600">
                <span>
                    <span class="font-semibold text-ink-900">Status Aktif</span>
                    <span class="mt-0.5 block text-xs leading-relaxed text-ink-500">
                        Akun nonaktif tetap tersimpan beserta seluruh jejak aktivitasnya, tetapi tidak dapat masuk ke dashboard.
                    </span>
                </span>
            </label>
        </div>

        <div class="mt-7 flex flex-wrap items-center gap-3 border-t border-ink-100 pt-6">
            <button type="submit" class="btn-primary px-6 py-2.5">
                {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Admin' }}
            </button>
            <a href="{{ route('superadmin.admins.index') }}" class="btn-outline">Batal</a>
        </div>
    </form>
@endsection
