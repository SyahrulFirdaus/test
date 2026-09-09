{{--
    Dropdown wilayah bertingkat: Provinsi → Kota/Kabupaten → Kecamatan → Kelurahan.

    Dipakai bersama oleh buku alamat pelanggan dan data perusahaan pada
    pendaftaran Business, sehingga keduanya berperilaku persis sama.

    Formulir yang memuatnya cukup menyediakan empat <select> ber-atribut:

        data-region-level="0..3"   urutan tingkatnya
        data-region-prompt="…"     tulisan saat tingkat ini siap dipilih
        data-region-locked="…"     tulisan saat induknya belum dipilih

    Setiap tingkat mengambil isinya dari endpoint milik induknya, lalu seluruh
    tingkat di bawahnya dikosongkan dan dikunci kembali. Server tetap memeriksa
    ulang rantai induknya saat penyimpanan (App\Support\RegionChain), jadi
    skrip ini murni soal kenyamanan.
--}}
@props(['selector'])

@php
    // Alamat endpoint tiap tingkat, dengan `__ID__` sebagai tempat induknya
    // disisipkan di browser. Disusun di sini, bukan langsung di dalam @json,
    // karena Blade tidak mengurai array bersarang multi-baris.
    $regionEndpoints = [
        route('regions.regencies', ['province' => '__ID__']),
        route('regions.districts', ['regency' => '__ID__']),
        route('regions.villages', ['district' => '__ID__']),
    ];
@endphp

<script>
    document.querySelectorAll(@json($selector)).forEach((form) => {
        const endpoints = @json($regionEndpoints);

        const levels = Array.from(form.querySelectorAll('[data-region-level]'))
            .sort((a, b) => Number(a.dataset.regionLevel) - Number(b.dataset.regionLevel));

        if (levels.length === 0) {
            return;
        }

        /** Kosongkan sebuah tingkat dan kunci kembali. */
        const reset = (select) => {
            select.innerHTML = '';
            select.append(new Option(select.dataset.regionLocked ?? select.dataset.regionPrompt, ''));
            select.value = '';
            select.disabled = true;
        };

        /** Isi sebuah tingkat dari daftar yang diterima. */
        const fill = (select, options) => {
            select.innerHTML = '';
            select.append(new Option(select.dataset.regionPrompt, ''));

            options.forEach((option) => select.append(new Option(option.name, option.id)));

            select.disabled = options.length === 0;
        };

        const load = async (index, parentId) => {
            const select = levels[index];

            if (!select) {
                return;
            }

            // Selama menunggu, tingkat ini dikunci agar tidak sempat diisi
            // dengan daftar yang sudah tidak berlaku.
            reset(select);
            select.querySelector('option').textContent = 'Memuat…';

            try {
                const response = await fetch(endpoints[index - 1].replace('__ID__', parentId), {
                    headers: { Accept: 'application/json' },
                });

                fill(select, response.ok ? await response.json() : []);
            } catch {
                fill(select, []);
                select.querySelector('option').textContent = 'Gagal memuat, coba pilih ulang';
            }
        };

        levels.forEach((select, index) => {
            select.addEventListener('change', () => {
                // Mengganti sebuah tingkat membatalkan seluruh pilihan di
                // bawahnya — data lama tidak boleh tertinggal.
                for (let below = index + 1; below < levels.length; below++) {
                    reset(levels[below]);
                }

                if (select.value) {
                    load(index + 1, select.value);
                }
            });
        });
    });
</script>
