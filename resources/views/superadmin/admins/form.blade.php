@extends('layouts.dashboard')

@php
    $isEdit = $admin->exists;

    // Setelah validasi gagal, posisi switch mengikuti kiriman terakhir (switch
    // yang mati memang tidak ikut terkirim); selain itu mengikuti yang tersimpan.
    $checkedPermissions = old('_token') !== null ? (array) old('permissions', []) : $granted;
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
          class="mt-6 max-w-6xl rounded-2xl border border-ink-100 bg-white p-6 shadow-card sm:p-7">
        @csrf
        @if ($isEdit) @method('PATCH') @endif

        {{-- Kiri: data akun. Kanan: hak akses. Berdampingan mulai lebar lg. --}}
        <div class="grid gap-8 lg:grid-cols-2 lg:gap-10">
        <div>
        <h2 class="font-display text-base font-bold text-ink-900">Data Akun</h2>

        <div class="mt-4">
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

        {{-- ================= HAK AKSES ADMIN =================
             Per modul: hak Lihat (menu) dan hak tindakan terpisah. Yang
             dimatikan hilang dari sidebar/tombol Admin ini DAN ditolak backend
             (403) bila dipanggil lewat URL. Daftarnya dari
             App\Support\AdminPermission. --}}
        </div>

        <fieldset class="border-t border-ink-100 pt-6 lg:border-l lg:border-t-0 lg:pl-10 lg:pt-0" data-permission-form>
            <legend class="sr-only">Hak Akses Admin</legend>
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="font-display text-base font-bold text-ink-900" aria-hidden="true">Hak Akses Admin</h2>
                    <p class="mt-1 text-xs leading-relaxed text-ink-500">
                        <span class="font-semibold text-ink-700">Lihat</span> membuka menu;
                        tindakan lain diperiksa terpisah oleh sistem. Menyalakan tindakan otomatis menyalakan Lihat
                        pada modulnya. Perubahan langsung berlaku setelah disimpan. Dashboard selalu dapat dibuka.
                    </p>
                </div>

                <div class="flex shrink-0 gap-2">
                    <button type="button" class="viewer-tool px-3 py-1.5 text-xs" data-permission-all="on">Pilih Semua</button>
                    <button type="button" class="viewer-tool px-3 py-1.5 text-xs" data-permission-all="off">Nonaktifkan Semua</button>
                </div>
            </div>

            <div class="mt-4 space-y-3">
                @foreach ($modules as $module => $group)
                    <div class="rounded-xl border border-ink-100" data-permission-module="{{ $module }}">
                        <div class="flex items-center justify-between gap-3 rounded-t-xl border-b border-ink-100 bg-ink-50/60 px-4 py-2.5">
                            <p class="font-display text-sm font-bold text-ink-900">{{ $group['label'] }}</p>

                            @if (count($group['permissions']) > 1)
                                <button type="button" class="text-xs font-semibold text-brand-600 transition-colors hover:text-brand-700"
                                        data-permission-module-toggle>
                                    Pilih Semua
                                </button>
                            @endif
                        </div>

                        <div class="divide-y divide-ink-100">
                            @foreach ($group['permissions'] as $key => $permission)
                                @php $on = in_array($key, $checkedPermissions, true); @endphp

                                <label class="flex cursor-pointer items-center justify-between gap-4 py-3 pl-4 pr-4" for="permission-{{ $key }}">
                                    <span class="flex min-w-0 items-start gap-2.5">
                                        <span class="mt-0.5 font-mono text-xs text-ink-300" aria-hidden="true">{{ $loop->last ? '└──' : '├──' }}</span>
                                        <span class="min-w-0">
                                            <span class="block text-sm font-semibold text-ink-900">
                                                {{ $permission['label'] }}
                                                <code class="ml-1 rounded bg-ink-50 px-1.5 py-0.5 font-mono text-[0.65rem] font-normal text-ink-400">{{ $key }}</code>
                                            </span>
                                            <span class="mt-0.5 block text-xs leading-relaxed text-ink-500">{{ $permission['description'] }}</span>
                                        </span>
                                    </span>

                                    <span class="relative inline-flex shrink-0 items-center gap-2.5">
                                        <input type="checkbox" role="switch"
                                               id="permission-{{ $key }}"
                                               name="permissions[]" value="{{ $key }}"
                                               class="peer sr-only" @checked($on)
                                               data-permission-key="{{ $key }}">
                                        <span class="w-7 text-right text-[0.65rem] font-bold uppercase tracking-[0.1em] text-ink-400 peer-checked:hidden">Off</span>
                                        <span class="hidden w-7 text-right text-[0.65rem] font-bold uppercase tracking-[0.1em] text-brand-700 peer-checked:inline">On</span>
                                        <span class="relative h-6 w-11 rounded-full bg-ink-200 transition-colors
                                                     peer-checked:bg-brand-600 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-brand-600
                                                     after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition-transform
                                                     peer-checked:after:translate-x-5"></span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            @error('permissions') <p class="field-error">{{ $message }}</p> @enderror
            @error('permissions.*') <p class="field-error">{{ $message }}</p> @enderror
        </fieldset>
        </div>

        <div class="mt-7 flex flex-wrap items-center gap-3 border-t border-ink-100 pt-6">
            <button type="submit" class="btn-primary px-6 py-2.5">
                {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Admin' }}
            </button>
            <a href="{{ route('superadmin.admins.index') }}" class="btn-outline">Batal</a>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        // Kenyamanan formulir saja — backend tetap menormalkan dan memeriksa
        // setiap hak akses (App\Support\AdminPermission::normalize()).
        (() => {
            const form = document.querySelector('[data-permission-form]');
            if (!form) return;

            const switches = (root) => Array.from(root.querySelectorAll('[data-permission-key]'));
            const viewOf = (module) => module.querySelector('[data-permission-key$=".view"]');

            const refresh = () => {
                form.querySelectorAll('[data-permission-module]').forEach((module) => {
                    const toggle = module.querySelector('[data-permission-module-toggle]');
                    if (toggle) {
                        toggle.textContent = switches(module).every((input) => input.checked) ? 'Batalkan Semua' : 'Pilih Semua';
                    }
                });
            };

            form.addEventListener('change', (event) => {
                const input = event.target.closest('[data-permission-key]');
                if (!input) return;

                const module = input.closest('[data-permission-module]');
                const view = viewOf(module);

                if (view && input !== view && input.checked) {
                    // Tindakan membutuhkan akses ke menunya.
                    view.checked = true;
                } else if (view && input === view && !input.checked) {
                    // Tanpa Lihat, tindakan pada modul ini tidak bermakna.
                    switches(module).forEach((other) => { other.checked = false; });
                }

                refresh();
            });

            form.querySelectorAll('[data-permission-module-toggle]').forEach((button) => {
                button.addEventListener('click', () => {
                    const module = button.closest('[data-permission-module]');
                    const turnOn = !switches(module).every((input) => input.checked);
                    switches(module).forEach((input) => { input.checked = turnOn; });
                    refresh();
                });
            });

            form.querySelectorAll('[data-permission-all]').forEach((button) => {
                button.addEventListener('click', () => {
                    const turnOn = button.dataset.permissionAll === 'on';
                    switches(form).forEach((input) => { input.checked = turnOn; });
                    refresh();
                });
            });

            refresh();
        })();
    </script>
@endpush
