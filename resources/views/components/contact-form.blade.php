{{--
    Form Kontak — satu komponen untuk About (#kontak), Services, dan setiap
    halaman industri.

    Kiriman masuk ke Lead Management (route leads.store, form=kontak) beserta
    halaman asal dan GCLID/UTM first touch, diemailkan ke tim CS, lalu
    pengunjung diarahkan ke /terima-kasih?tipe=kontak (event generate_lead).

    Anti-spam: honeypot + batas waktu pengisian, ditambah reCAPTCHA v3 bila
    kuncinya diisi di .env (config leads.recaptcha).

    Props:
      service     — layanan yang terpilih lebih dulu (opsional)
      embedded    — true: hanya kartu formulir, tanpa <section> dan judul
      eyebrow, title, description — judul bagian (bila tidak embedded)
--}}
@props([
    'service' => null,
    'embedded' => false,
    'eyebrow' => 'Form Kontak',
    'title' => 'Ceritakan kebutuhan Anda',
    'description' => 'Isi form singkat ini tanpa perlu membuat akun. Lampirkan file 3D, gambar kerja, atau foto bila ada — tim kami meninjaunya dan menghubungi Anda.',
])

@php
    $attachment = config('leads.contact.attachment');
    $accept = collect($attachment['extensions'])->map(fn ($ext) => '.'.$ext)->implode(',');
    $maxMb = $attachment['max_kb'] / 1024;
    $recaptchaKey = app(\App\Services\Recaptcha::class)->enabled() ? config('leads.recaptcha.site_key') : null;
    $inputClass = 'mt-2 w-full rounded-xl border border-ink-200 bg-white px-4 py-3 text-sm text-ink-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 aria-[invalid=true]:border-brand-600';
    $selected = old('form') === 'kontak' ? old('service') : $service;
    $hasErrors = old('form') === 'kontak' && $errors->any();
    $error = fn (string $field) => $hasErrors ? $errors->first($field) : '';
@endphp

@unless ($embedded)
    <section id="form-kontak" class="section scroll-mt-24 bg-ink-50/70">
        <div class="container-page">
            <div class="grid gap-10 lg:grid-cols-12 lg:gap-14">
                <div class="lg:col-span-5">
                    <x-section-heading align="left" :eyebrow="$eyebrow" :title="$title" :description="$description" />

                    <ul class="mt-8 space-y-3 text-sm text-ink-600">
                        <li class="flex gap-2.5"><x-icons.check class="mt-0.5 h-4 w-4 shrink-0 text-brand-600" /> Ditinjau tim engineer pada jam kerja berikutnya.</li>
                        <li class="flex gap-2.5"><x-icons.check class="mt-0.5 h-4 w-4 shrink-0 text-brand-600" /> File Anda dijaga kerahasiaannya; NDA tersedia.</li>
                        <li class="flex gap-2.5"><x-icons.check class="mt-0.5 h-4 w-4 shrink-0 text-brand-600" /> Lampiran STL, STEP, OBJ, 3MF, PDF, atau JPG hingga {{ $maxMb }} MB.</li>
                    </ul>
                </div>

                <div class="lg:col-span-7">
@endunless

<form action="{{ route('leads.store') }}" method="POST" enctype="multipart/form-data" novalidate
      @if ($embedded) id="form-kontak" @endif
      class="scroll-mt-28 rounded-3xl border border-ink-100 bg-white p-6 shadow-card sm:p-8"
      data-contact-form
      @if ($recaptchaKey) data-recaptcha-key="{{ $recaptchaKey }}" @endif>
    @csrf
    <input type="hidden" name="form" value="kontak">
    <input type="hidden" name="page_url" value="{{ url()->current() }}">
    <input type="hidden" name="form_started" value="{{ \Illuminate\Support\Facades\Crypt::encryptString((string) now()->timestamp) }}">
    <input type="hidden" name="recaptcha_token" value="">

    {{-- Jebakan bot: tersembunyi dari pengunjung, tidak pernah diisi manusia. --}}
    <div class="hidden" aria-hidden="true">
        <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
    </div>

    <div class="{{ $hasErrors ? '' : 'hidden' }} mb-6 rounded-2xl border border-brand-200 bg-brand-50 p-4 text-sm font-semibold text-brand-800"
         role="alert" tabindex="-1" data-form-error>
        {{ $hasErrors ? ($errors->first('form') ?: 'Periksa kembali isian yang ditandai.') : '' }}
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        @foreach ([
            ['name' => 'name', 'label' => 'Nama', 'type' => 'text', 'required' => true, 'max' => 100, 'autocomplete' => 'name'],
            ['name' => 'company', 'label' => 'Perusahaan', 'type' => 'text', 'required' => false, 'max' => 120, 'autocomplete' => 'organization'],
            ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true, 'max' => 120, 'autocomplete' => 'email'],
            ['name' => 'whatsapp', 'label' => 'WhatsApp', 'type' => 'tel', 'required' => true, 'max' => 30, 'autocomplete' => 'tel', 'placeholder' => '08xx-xxxx-xxxx', 'phone' => true],
        ] as $field)
            @php $fieldId = 'kontak-'.$field['name']; @endphp
            <div>
                <label for="{{ $fieldId }}" class="text-sm font-semibold text-ink-700">
                    {{ $field['label'] }}
                    @if ($field['required'])
                        <span class="text-brand-600">*</span>
                    @else
                        <span class="font-normal text-ink-400">(opsional)</span>
                    @endif
                </label>
                <input id="{{ $fieldId }}" name="{{ $field['name'] }}" type="{{ $field['type'] }}"
                       value="{{ old('form') === 'kontak' ? old($field['name']) : '' }}"
                       maxlength="{{ $field['max'] }}" autocomplete="{{ $field['autocomplete'] }}"
                       @if ($field['required']) required @endif
                       @isset($field['placeholder']) placeholder="{{ $field['placeholder'] }}" @endisset
                       @isset($field['phone']) inputmode="tel" data-phone @endisset
                       @if ($error($field['name'])) aria-invalid="true" @endif
                       aria-describedby="{{ $fieldId }}-error"
                       class="{{ $inputClass }}">
                <p id="{{ $fieldId }}-error" class="mt-1.5 text-xs font-semibold text-brand-700" data-error-for="{{ $field['name'] }}">{{ $error($field['name']) }}</p>
            </div>
        @endforeach

        <div class="sm:col-span-2">
            <label for="kontak-service" class="text-sm font-semibold text-ink-700">Layanan <span class="text-brand-600">*</span></label>
            <select id="kontak-service" name="service" required aria-describedby="kontak-service-error"
                    @if ($error('service')) aria-invalid="true" @endif
                    class="{{ $inputClass }}">
                <option value="">Pilih layanan</option>
                @foreach (config('leads.contact.services') as $option)
                    <option value="{{ $option }}" @selected($selected === $option)>{{ $option }}</option>
                @endforeach
            </select>
            <p id="kontak-service-error" class="mt-1.5 text-xs font-semibold text-brand-700" data-error-for="service">{{ $error('service') }}</p>
        </div>

        <div class="sm:col-span-2">
            <label for="kontak-notes" class="text-sm font-semibold text-ink-700">Kebutuhan <span class="text-brand-600">*</span></label>
            <textarea id="kontak-notes" name="notes" rows="4" required maxlength="2000"
                      placeholder="Contoh: jumlah part, ukuran, material, tenggat, atau fungsi part."
                      @if ($error('notes')) aria-invalid="true" @endif
                      aria-describedby="kontak-notes-error"
                      class="{{ $inputClass }}">{{ old('form') === 'kontak' ? old('notes') : '' }}</textarea>
            <p id="kontak-notes-error" class="mt-1.5 text-xs font-semibold text-brand-700" data-error-for="notes">{{ $error('notes') }}</p>
        </div>

        <div class="sm:col-span-2">
            <label for="kontak-attachment" class="text-sm font-semibold text-ink-700">Lampiran File <span class="font-normal text-ink-400">(opsional)</span></label>
            <input id="kontak-attachment" name="attachment" type="file" accept="{{ $accept }}"
                   data-max-kb="{{ $attachment['max_kb'] }}"
                   aria-describedby="kontak-attachment-help kontak-attachment-error"
                   class="mt-2 block w-full cursor-pointer rounded-xl border border-dashed border-ink-200 px-4 py-3 text-sm text-ink-600 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
            <p id="kontak-attachment-help" class="mt-1.5 text-xs text-ink-400">STL, STEP, OBJ, 3MF, PDF, atau JPG — maksimal {{ $maxMb }} MB.</p>
            <p id="kontak-attachment-error" class="mt-1 text-xs font-semibold text-brand-700" data-error-for="attachment">{{ $error('attachment') }}</p>
            <div class="mt-2 hidden h-1.5 overflow-hidden rounded-full bg-ink-100" data-upload-progress>
                <div class="h-full w-0 bg-brand-600 transition-[width]" data-upload-bar></div>
            </div>
        </div>
    </div>

    <button type="submit" class="btn-primary mt-6 w-full sm:w-auto" data-cta="form-kontak" data-submit>
        Kirim
    </button>

    <x-legal-note action="mengirim form ini" class="mt-4" />

    @if ($recaptchaKey)
        <p class="mt-2 text-[0.65rem] leading-relaxed text-ink-400">
            Dilindungi reCAPTCHA; berlaku <a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer" class="underline">Kebijakan Privasi</a>
            dan <a href="https://policies.google.com/terms" target="_blank" rel="noopener noreferrer" class="underline">Persyaratan Layanan</a> Google.
        </p>
    @endif
</form>

@unless ($embedded)
                </div>
            </div>
        </div>
    </section>
@endunless

@once
    @push('scripts')
        <script>
            (function () {
                document.querySelectorAll('[data-contact-form]').forEach(function (form) {
                    if (!window.FormData || !window.XMLHttpRequest) {
                        return;
                    }

                    var failure = form.querySelector('[data-form-error]');
                    var button = form.querySelector('[data-submit]');
                    var file = form.querySelector('[name="attachment"]');
                    var progress = form.querySelector('[data-upload-progress]');
                    var bar = form.querySelector('[data-upload-bar]');
                    var recaptchaKey = form.dataset.recaptchaKey;
                    var buttonLabel = button.textContent.trim();

                    var messages = {
                        name: 'Nama wajib diisi.',
                        email: 'Masukkan email yang valid.',
                        whatsapp: 'Masukkan nomor WhatsApp yang valid (angka saja).',
                        service: 'Pilih layanan.',
                        notes: 'Kebutuhan wajib diisi.'
                    };

                    // reCAPTCHA v3 dimuat hanya saat formulir mulai dipakai.
                    function loadRecaptcha() {
                        if (!recaptchaKey || window.grecaptcha || document.querySelector('script[data-recaptcha]')) {
                            return;
                        }

                        var script = document.createElement('script');
                        script.src = 'https://www.google.com/recaptcha/api.js?render=' + encodeURIComponent(recaptchaKey);
                        script.async = true;
                        script.dataset.recaptcha = '1';
                        document.head.appendChild(script);
                    }

                    form.addEventListener('focusin', loadRecaptcha, { once: true });

                    function token() {
                        return new Promise(function (resolve) {
                            if (!recaptchaKey || !window.grecaptcha) {
                                resolve('');
                                return;
                            }

                            window.grecaptcha.ready(function () {
                                window.grecaptcha.execute(recaptchaKey, { action: 'contact' }).then(resolve, function () { resolve(''); });
                            });
                        });
                    }

                    function setError(name, message) {
                        var field = form.querySelector('[name="' + name + '"]');
                        var slot = form.querySelector('[data-error-for="' + name + '"]');

                        if (field) {
                            field.setAttribute('aria-invalid', message ? 'true' : 'false');
                        }

                        if (slot) {
                            slot.textContent = message || '';
                        }
                    }

                    function showFailure(message) {
                        failure.textContent = message;
                        failure.classList.remove('hidden');
                        failure.focus();
                    }

                    function validate() {
                        var firstInvalid = null;

                        Object.keys(messages).forEach(function (name) {
                            var field = form.querySelector('[name="' + name + '"]');
                            var value = field.value.trim();
                            var valid = value !== '' && field.checkValidity()
                                && (!field.hasAttribute('data-phone') || /^[0-9+()\-\s.]{6,30}$/.test(value));

                            setError(name, valid ? '' : messages[name]);
                            firstInvalid = firstInvalid || (valid ? null : field);
                        });

                        setError('attachment', '');

                        if (file.files.length) {
                            var picked = file.files[0];
                            var extension = '.' + picked.name.split('.').pop().toLowerCase();

                            if (file.accept.split(',').indexOf(extension) === -1) {
                                setError('attachment', 'Lampiran harus berupa file STL, STEP, OBJ, 3MF, PDF, atau JPG.');
                                firstInvalid = firstInvalid || file;
                            } else if (picked.size > Number(file.dataset.maxKb) * 1024) {
                                setError('attachment', 'Ukuran lampiran maksimal ' + Math.round(Number(file.dataset.maxKb) / 1024) + ' MB.');
                                firstInvalid = firstInvalid || file;
                            }
                        }

                        if (firstInvalid) {
                            firstInvalid.focus();
                        }

                        return !firstInvalid;
                    }

                    function done() {
                        button.disabled = false;
                        button.textContent = buttonLabel;
                        progress.classList.add('hidden');
                    }

                    form.addEventListener('submit', function (event) {
                        event.preventDefault();
                        failure.classList.add('hidden');

                        if (!validate()) {
                            return;
                        }

                        button.disabled = true;
                        button.textContent = 'Mengirim…';

                        token().then(function (value) {
                            form.querySelector('[name="recaptcha_token"]').value = value;

                            // XMLHttpRequest supaya kemajuan unggahan file besar terlihat.
                            var xhr = new XMLHttpRequest();
                            xhr.open('POST', form.action);
                            xhr.setRequestHeader('Accept', 'application/json');
                            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                            if (file.files.length) {
                                progress.classList.remove('hidden');
                                xhr.upload.addEventListener('progress', function (e) {
                                    if (e.lengthComputable) {
                                        bar.style.width = Math.round((e.loaded / e.total) * 100) + '%';
                                        button.textContent = 'Mengunggah… ' + Math.round((e.loaded / e.total) * 100) + '%';
                                    }
                                });
                            }

                            xhr.onload = function () {
                                var body = {};

                                try {
                                    body = JSON.parse(xhr.responseText || '{}');
                                } catch (error) {}

                                if (xhr.status === 201 && body.redirect) {
                                    window.location.href = body.redirect;
                                    return;
                                }

                                done();

                                if (xhr.status === 422 && body.errors) {
                                    Object.keys(body.errors).forEach(function (name) {
                                        setError(name, body.errors[name][0]);
                                    });
                                    showFailure('Periksa kembali isian yang ditandai.');
                                } else if (xhr.status === 413) {
                                    setError('attachment', 'File terlalu besar untuk diunggah.');
                                    showFailure('File terlalu besar untuk diunggah.');
                                } else if (xhr.status === 419) {
                                    showFailure('Sesi halaman sudah kedaluwarsa. Muat ulang halaman lalu kirim kembali.');
                                } else if (xhr.status === 429) {
                                    showFailure('Terlalu banyak percobaan. Tunggu sebentar lalu coba lagi.');
                                } else {
                                    showFailure(body.message || 'Formulir belum dapat dikirim. Silakan coba lagi atau hubungi kami melalui WhatsApp.');
                                }
                            };

                            xhr.onerror = function () {
                                done();
                                showFailure('Koneksi terputus. Periksa internet Anda lalu coba lagi.');
                            };

                            xhr.send(new FormData(form));
                        });
                    });
                });
            })();
        </script>
    @endpush
@endonce
