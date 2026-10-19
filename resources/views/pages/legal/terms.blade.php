@extends('layouts.app')

@section('title', 'Syarat & Ketentuan')
@section('og-image', 'legal')
@section('description', 'Syarat & Ketentuan penggunaan website dan layanan '.$company->name.': akun, file desain, penawaran, pembayaran, produksi, pengiriman, dan pembatalan.')

@php
    $legalName = $company->legal_name ?: $company->name;
    // Angka di bawah ini dibaca dari konfigurasi sistem agar selalu sama
    // dengan cara kerja aplikasi (config/payment.php).
    $paymentWindow = (int) config('payment.window_hours', 24);
    $bank = config('payment.bank', []);
    $proofFormats = strtoupper(implode(', ', config('payment.proof.extensions', [])));
    $proofMaxMb = round(((int) config('payment.proof.max_kilobytes', 0)) / 1024);

    $sections = [
        ['id' => 'umum', 'title' => 'Ketentuan Umum'],
        ['id' => 'akun', 'title' => 'Akun Pengguna'],
        ['id' => 'layanan', 'title' => 'Layanan Kami'],
        ['id' => 'file', 'title' => 'File Desain & Hak Kekayaan Intelektual'],
        ['id' => 'penawaran', 'title' => 'Estimasi & Penawaran Harga'],
        ['id' => 'pembayaran', 'title' => 'Pembayaran'],
        ['id' => 'produksi', 'title' => 'Produksi & Waktu Pengerjaan'],
        ['id' => 'hasil', 'title' => 'Hasil Cetak & Toleransi'],
        ['id' => 'pengiriman', 'title' => 'Pengiriman'],
        ['id' => 'pembatalan', 'title' => 'Pembatalan Pesanan'],
        ['id' => 'keluhan', 'title' => 'Keluhan & Perbaikan'],
        ['id' => 'tanggung-jawab', 'title' => 'Batasan Tanggung Jawab'],
        ['id' => 'keadaan-kahar', 'title' => 'Keadaan Kahar'],
        ['id' => 'hukum', 'title' => 'Hukum yang Berlaku'],
        ['id' => 'perubahan', 'title' => 'Perubahan Ketentuan'],
        ['id' => 'kontak', 'title' => 'Hubungi Kami'],
    ];
@endphp

@section('content')
    <x-legal-page
        title="Syarat & Ketentuan"
        description="Ketentuan yang berlaku saat Anda menggunakan website dan layanan 3D printing kami. Mohon dibaca sebelum mengirim permintaan penawaran."
        updated="15 Oktober 2026"
        :sections="$sections"
        :other="['url' => route('legal.privacy'), 'label' => 'Baca Kebijakan Privasi']">

        <section id="umum">
            <h2>1. Ketentuan Umum</h2>
            <p>
                Syarat & Ketentuan ini mengatur penggunaan website dan layanan yang disediakan oleh {{ $legalName }}
                ("<strong>{{ $company->name }}</strong>", "kami") kepada Anda ("pelanggan"). Dengan membuat akun, mengirim permintaan
                penawaran, atau menggunakan layanan kami, Anda menyatakan telah membaca, memahami, dan menyetujui Syarat & Ketentuan ini
                serta <a href="{{ route('legal.privacy') }}">Kebijakan Privasi</a> kami.
            </p>
            <p>Apabila Anda tidak menyetujui sebagian atau seluruh ketentuan ini, mohon untuk tidak menggunakan layanan kami.</p>
        </section>

        <section id="akun">
            <h2>2. Akun Pengguna</h2>
            <ul>
                <li>Anda wajib memberikan data yang benar, lengkap, dan terkini saat mendaftar, serta memperbaruinya bila ada perubahan.</li>
                <li>Anda bertanggung jawab menjaga kerahasiaan kata sandi dan atas seluruh aktivitas yang terjadi melalui akun Anda.</li>
                <li>Akun bisnis hanya boleh didaftarkan oleh pihak yang berwenang mewakili perusahaan tersebut.</li>
                <li>Kami berhak menangguhkan atau menonaktifkan akun yang terindikasi memberikan data palsu, melakukan penyalahgunaan, atau melanggar ketentuan ini.</li>
            </ul>
        </section>

        <section id="layanan">
            <h2>3. Layanan Kami</h2>
            <p>
                Kami menyediakan layanan 3D printing dengan teknologi FDM, SLA, MJF, dan SLM, beserta layanan pendukung seperti
                desain 3D, 3D scanning, reverse engineering, pengembangan produk, serta paint & finishing. Ketersediaan teknologi,
                material, dan warna dapat berubah sewaktu-waktu sesuai kondisi produksi.
            </p>
        </section>

        <section id="file">
            <h2>4. File Desain & Hak Kekayaan Intelektual</h2>
            <ul>
                <li>Anda menjamin bahwa Anda memiliki hak penuh atau izin yang sah atas setiap file dan desain yang Anda kirimkan, dan bahwa pencetakannya tidak melanggar hak kekayaan intelektual pihak lain.</li>
                <li>Hak kekayaan intelektual atas desain Anda tetap menjadi milik Anda. Kami hanya menggunakan file tersebut untuk meninjau, menyusun penawaran, dan memproduksi pesanan Anda, sebagaimana dijelaskan dalam <a href="{{ route('legal.privacy') }}#file-model">Kebijakan Privasi</a>.</li>
                <li>Kami berhak menolak mencetak objek yang melanggar hukum, membahayakan keselamatan, atau melanggar hak pihak lain, termasuk namun tidak terbatas pada senjata api beserta komponennya.</li>
                <li>Anda bertanggung jawab penuh atas segala tuntutan pihak ketiga yang timbul akibat desain yang Anda kirimkan.</li>
            </ul>
        </section>

        <section id="penawaran">
            <h2>5. Estimasi & Penawaran Harga</h2>
            <ul>
                <li>Estimasi biaya dan waktu yang ditampilkan otomatis di halaman 3D Models bersifat <strong>perkiraan</strong> dan belum mengikat.</li>
                <li>Setiap permintaan penawaran ditinjau oleh tim engineer kami. Harga, teknologi, material, dan waktu pengerjaan final ditetapkan dalam penawaran resmi setelah peninjauan tersebut.</li>
                <li>Kami dapat memberikan rekomendasi perubahan desain atau spesifikasi apabila file berisiko gagal cetak. Perubahan hanya dilakukan dengan persetujuan Anda.</li>
                <li>Selama file masih berstatus "File Sedang Direview", Anda masih dapat mengubah isi permintaan melalui dashboard.</li>
            </ul>
        </section>

        <section id="pembayaran">
            <h2>6. Pembayaran</h2>
            <ul>
                <li>
                    Pembayaran dilakukan melalui transfer ke rekening resmi perusahaan
                    @if (! empty($bank['name']))
                        ({{ $bank['name'] }} a.n. {{ $bank['account_holder'] ?? $legalName }})
                    @endif
                    sebagaimana tercantum di halaman pembayaran pada dashboard Anda. Kami tidak pernah meminta transfer ke rekening pribadi.
                </li>
                <li>Pembayaran wajib dilakukan dalam waktu <strong>{{ $paymentWindow }} jam</strong> sejak status penawaran berubah menjadi "Menunggu Pembayaran". Apabila batas waktu terlewati, penawaran dibatalkan otomatis oleh sistem.</li>
                <li>Setelah transfer, unggah bukti pembayaran melalui dashboard @if ($proofFormats) (format {{ $proofFormats }}, maksimal {{ $proofMaxMb }} MB) @endif. Pesanan diproses setelah pembayaran diverifikasi oleh tim kami.</li>
                <li>Pelanggan bisnis dapat mengajukan pembayaran bertahap (termin) sesuai ketentuan dan jadwal yang disetujui. Setiap termin wajib dibayar sesuai tanggal jatuh temponya.</li>
                <li>Bukti pembayaran yang tidak valid atau tidak sesuai dapat kami tolak, dan Anda akan diminta mengunggah ulang.</li>
            </ul>
        </section>

        <section id="produksi">
            <h2>7. Produksi & Waktu Pengerjaan</h2>
            <ul>
                <li>Waktu pengerjaan dihitung sejak pembayaran diterima, sudah termasuk antrean produksi, post-processing, dan quality control.</li>
                <li>Waktu pengerjaan yang tercantum merupakan estimasi. Tanggal pastinya dikonfirmasi oleh tim kami saat penawaran ditinjau.</li>
                <li>Opsi pengerjaan Express hanya tersedia bila pesanan memenuhi syarat yang ditentukan sistem; bila tidak memenuhi, pesanan diproses dengan opsi Standard.</li>
                <li>Perkembangan pesanan dapat dipantau melalui dashboard maupun halaman Tracking dengan nomor penawaran Anda.</li>
            </ul>
        </section>

        <section id="hasil">
            <h2>8. Hasil Cetak & Toleransi</h2>
            <ul>
                <li>Setiap teknologi 3D printing memiliki karakteristik tersendiri, seperti garis layer, tekstur permukaan, dan toleransi dimensi, sebagaimana dijelaskan di halaman Technologies.</li>
                <li>Warna hasil cetak dapat sedikit berbeda dari tampilan di layar karena perbedaan perangkat dan pencahayaan.</li>
                <li>Kekuatan dan fungsi part sangat dipengaruhi oleh desain dari pelanggan. Kami tidak menjamin kesesuaian part untuk penggunaan tertentu kecuali telah disepakati secara tertulis.</li>
            </ul>
        </section>

        <section id="pengiriman">
            <h2>9. Pengiriman</h2>
            <ul>
                <li>Pesanan dikirim ke alamat yang Anda pilih melalui jasa ekspedisi. Pastikan alamat dan nomor telepon penerima sudah benar.</li>
                <li>Kami mengemas setiap pesanan dengan aman. Keterlambatan atau kerusakan yang terjadi selama proses pengiriman berada di bawah tanggung jawab jasa ekspedisi, namun kami akan membantu proses klaimnya.</li>
            </ul>
        </section>

        <section id="pembatalan">
            <h2>10. Pembatalan Pesanan</h2>
            <ul>
                <li>Selama status masih "File Sedang Direview", Anda dapat membatalkan permintaan secara langsung melalui dashboard.</li>
                <li>Setelah tahap tersebut, pembatalan dilakukan dengan mengajukan permohonan melalui dashboard dan memerlukan persetujuan tim kami.</li>
                <li>Pesanan yang sudah selesai tidak dapat dibatalkan.</li>
                <li>Untuk pesanan yang telah dibayar dan sudah masuk proses produksi, pengembalian dana disesuaikan dengan tahap pengerjaan dan biaya yang telah dikeluarkan, serta dibahas bersama pelanggan.</li>
            </ul>
        </section>

        <section id="keluhan">
            <h2>11. Keluhan & Perbaikan</h2>
            <p>
                Mohon periksa pesanan segera setelah diterima. Apabila terdapat ketidaksesuaian dengan spesifikasi yang disepakati atau
                kerusakan akibat proses produksi kami, hubungi kami sesegera mungkin dengan menyertakan nomor penawaran serta foto
                atau video kondisi barang. Setelah keluhan diverifikasi, kami akan menawarkan perbaikan, pencetakan ulang, atau solusi
                lain yang disepakati bersama.
            </p>
        </section>

        <section id="tanggung-jawab">
            <h2>12. Batasan Tanggung Jawab</h2>
            <ul>
                <li>Kami tidak bertanggung jawab atas kesalahan desain, dimensi, atau spesifikasi yang berasal dari file pelanggan.</li>
                <li>Kami tidak bertanggung jawab atas kerugian yang timbul akibat penggunaan part di luar peruntukan atau spesifikasi materialnya.</li>
                <li>Sejauh diizinkan oleh hukum, total tanggung jawab kami atas suatu pesanan tidak melebihi nilai pesanan yang telah dibayarkan.</li>
            </ul>
        </section>

        <section id="keadaan-kahar">
            <h2>13. Keadaan Kahar</h2>
            <p>
                Kami tidak bertanggung jawab atas keterlambatan atau kegagalan pelaksanaan kewajiban yang disebabkan oleh keadaan di luar
                kendali wajar kami, seperti bencana alam, kebakaran, gangguan listrik atau jaringan, kebijakan pemerintah, maupun kelangkaan
                material. Dalam keadaan tersebut, kami akan memberitahukan Anda dan berupaya mencari solusi terbaik.
            </p>
        </section>

        <section id="hukum">
            <h2>14. Hukum yang Berlaku</h2>
            <p>
                Syarat & Ketentuan ini tunduk pada hukum Republik Indonesia. Setiap perselisihan akan diselesaikan terlebih dahulu
                secara musyawarah untuk mufakat. Apabila tidak tercapai kesepakatan, perselisihan diselesaikan melalui pengadilan negeri
                di wilayah hukum domisili perusahaan.
            </p>
        </section>

        <section id="perubahan">
            <h2>15. Perubahan Ketentuan</h2>
            <p>
                Kami dapat mengubah Syarat & Ketentuan ini sewaktu-waktu. Versi terbaru akan selalu tersedia di halaman ini. Pesanan yang
                sudah berjalan tetap mengikuti ketentuan yang berlaku pada saat penawaran disetujui.
            </p>
        </section>

        <section id="kontak">
            <h2>16. Hubungi Kami</h2>
            <ul>
                <li><strong>{{ $legalName }}</strong></li>
                @if ($company->email)
                    <li>Email: <a href="mailto:{{ $company->email }}" data-cta="content">{{ $company->email }}</a></li>
                @endif
                @if ($company->whatsapp_link)
                    <li>WhatsApp: <a href="{{ wa_link() }}" target="_blank" rel="noopener noreferrer" data-wa-link data-cta="content">{{ $company->whatsapp }}</a></li>
                @endif
                @if ($company->address)
                    <li>Alamat: {{ $company->address }}</li>
                @endif
            </ul>
        </section>
    </x-legal-page>
@endsection
