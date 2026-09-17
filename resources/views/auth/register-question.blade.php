{{--
    Satu pertanyaan pendaftaran.

    Bentuk inputnya mengikuti kolom `type` pada tabel pertanyaan, dan pilihannya
    diambil dari `resolvedOptions()` — yang untuk pertanyaan teknologi dan
    material dibaca dari data sistem, bukan ditulis di sini.

    Nilai terisi diambil berurutan dari old() (setelah validasi gagal), jawaban
    yang tersimpan di sesi (saat pengguna menekan Kembali), lalu nilai bawaan
    dari data akun bila pertanyaannya memang sudah terjawab di sana.
--}}
@php
    $field = 'questions.'.$question->id;
    $name = 'questions['.$question->id.']';

    $saved = $answers[$question->id] ?? [];
    $default = $prefill[$question->key] ?? null;

    $selected = (array) old($field, $saved !== [] ? $saved : array_filter([$default]));
    $single = $selected[0] ?? '';
@endphp

<fieldset>
    <legend class="field-label">
        {{ $question->question }}
        @if ($question->is_required)
            <span class="text-brand-600">*</span>
        @else
            {{-- Pertanyaan opsional ditandai jelas supaya tidak ada yang merasa
                 harus menjawab hal yang belum mereka pahami. --}}
            <span class="ml-1 rounded-full bg-ink-100 px-2 py-0.5 text-[0.6rem] normal-case tracking-normal text-ink-500">Opsional</span>
        @endif
    </legend>

    @if ($question->help)
        <p class="mt-1.5 text-xs leading-relaxed text-ink-400">{{ $question->help }}</p>
    @endif

    @if ($question->type === 'select')
        <select name="{{ $name }}" id="{{ $field }}" class="field-input" @required($question->is_required)>
            <option value="">Pilih salah satu</option>
            @foreach ($question->resolvedOptions() as $option)
                <option value="{{ $option }}" @selected($single === $option)>{{ $option }}</option>
            @endforeach
        </select>

    @elseif ($question->isChoice())
        @php $multiple = $question->isMultiple(); @endphp

        <div class="mt-3 grid gap-2.5 sm:grid-cols-2">
            @foreach ($question->resolvedOptions() as $index => $option)
                @php $id = $field.'-'.$index; @endphp

                <div>
                    <input type="{{ $multiple ? 'checkbox' : 'radio' }}"
                           id="{{ $id }}"
                           name="{{ $name }}{{ $multiple ? '[]' : '' }}"
                           value="{{ $option }}"
                           class="peer sr-only"
                           @checked(in_array($option, $selected, true))>

                    <label for="{{ $id }}" class="choice-option">
                        <span class="choice-marker {{ $multiple ? 'rounded-md' : 'rounded-full' }}">
                            @if ($multiple)
                                <x-icons.check class="choice-dot h-2.5 w-2.5" />
                            @else
                                <span class="choice-dot h-2 w-2 rounded-full bg-white"></span>
                            @endif
                        </span>

                        <span class="font-semibold">{{ $option }}</span>
                    </label>
                </div>
            @endforeach
        </div>

    @elseif ($question->type === 'textarea')
        <textarea id="{{ $field }}"
                  name="{{ $name }}"
                  rows="4"
                  class="field-input"
                  maxlength="2000"
                  placeholder="{{ $question->placeholder }}"
                  @required($question->is_required)>{{ $single }}</textarea>

    @else
        <input type="{{ $question->type === 'email' ? 'email' : ($question->type === 'url' ? 'url' : 'text') }}"
               id="{{ $field }}"
               name="{{ $name }}"
               value="{{ $single }}"
               class="field-input"
               maxlength="255"
               placeholder="{{ $question->placeholder }}"
               @required($question->is_required)>
    @endif

    @error($field) <p class="field-error">{{ $message }}</p> @enderror
    @error($field.'.*') <p class="field-error">{{ $message }}</p> @enderror
</fieldset>
