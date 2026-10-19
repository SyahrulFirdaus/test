@extends('layouts.app')

@section('title', 'Kebijakan Privasi')
@section('og-image', 'legal')
@section('description', 'Kebijakan Privasi '.$company->name.': data apa yang kami kumpulkan, bagaimana kami menggunakannya, cara kami melindunginya, serta hak Anda atas data pribadi.')

@php
    $legalName = $company->legal_name ?: $company->name;
    $sections = [
        ['id' => 'pendahuluan', 'title' => 'Pendahuluan'],
        ['id' => 'data-dikumpulkan', 'title' => 'Data yang Kami Kumpulkan'],
        ['id' => 'file-model', 'title' => 'File Model 3D & Kerahasiaan Desain'],
        ['id' => 'penggunaan', 'title' => 'Cara Kami Menggunakan Data'],
        ['id' => 'dasar', 'title' => 'Dasar Pemrosesan'],
        ['id' => 'pihak-ketiga', 'title' => 'Berbagi Data dengan Pihak Ketiga'],
        ['id' => 'cookie', 'title' => 'Cookie & Penyimpanan Browser'],
        ['id' => 'keamanan', 'title' => 'Penyimpanan & Keamanan Data'],
        ['id' => 'hak', 'title' => 'Hak Anda'],
        ['id' => 'anak', 'title' => 'Pengguna di Bawah Umur'],
        ['id' => 'perubahan', 'title' => 'Perubahan Kebijakan'],
        ['id' => 'kontak', 'title' => 'Hubungi Kami'],
    ];
@endphp

@section('content')
    <x-legal-page
        title="Kebijakan Privasi"
        description="Bagaimana kami mengumpulkan, menggunakan, dan melindungi data pribadi Anda saat menggunakan website dan layanan kami."
        updated="15 Oktober 2026"
        :sections="$sections"
        :other="['url' => route('legal.terms'), 'label' => 'Baca Syarat & Ketentuan']">

        <section id="pendahuluan">
            <h2>1. Pendahuluan</h2>
            <p>
                Kebijakan Privasi ini menjelaskan bagaimana {{ $legalName }} ("<strong>{{ $company->name }}</strong>", "kami")
                mengumpulkan, menggunakan, menyimpan, dan melindungi data pribadi Anda ketika Anda mengunjungi website ini,
                membuat akun, mengunggah file model 3D, mengajukan permintaan penawaran, maupun menggunakan layanan kami lainnya.
            </p>
            <p>
                Kami memproses data pribadi dengan mengacu pada Undang-Undang Nomor 27 Tahun 2022 tentang Pelindungan Data Pribadi
                beserta peraturan pelaksanaannya. Dengan menggunakan website dan layanan kami, Anda menyatakan telah membaca dan
                memahami Kebijakan Privasi ini.
            </p>
        </section>

        <section id="data-dikumpulkan">
            <h2>2. Data yang Kami Kumpulkan</h2>
            <p>Data yang kami kumpulkan bergantung pada cara Anda menggunakan layanan kami:</p>
            <ul>
                <li><strong>Data akun:</strong> nama, alamat email, nomor telepon/WhatsApp, jenis pelanggan (personal atau bisnis), kota, kode pos, alamat, dan kata sandi (disimpan dalam bentuk terenkripsi/hash, tidak dapat kami baca).</li>
                <li><strong>Data perusahaan</strong> (khusus akun bisnis): nama perusahaan, nama dan jabatan penanggung jawab (PIC), telepon dan email perusahaan, website, bidang industri, serta alamat perusahaan.</li>
                <li><strong>Jawaban kuesioner pendaftaran:</strong> misalnya tujuan penggunaan, frekuensi kebutuhan, jenis proyek, teknologi, dan material yang biasa Anda gunakan.</li>
                <li><strong>Alamat pengiriman:</strong> nama dan nomor telepon penerima, alamat lengkap, kode pos, serta catatan pengiriman.</li>
                <li><strong>Data pesanan:</strong> file model 3D yang Anda kirim, spesifikasi cetak (teknologi, material, warna, jumlah, finishing, dan lainnya), catatan pesanan, serta riwayat status penawaran.</li>
                <li><strong>Data pembayaran:</strong> bukti transfer yang Anda unggah beserta informasi termin pembayaran. Kami <strong>tidak</strong> meminta maupun menyimpan nomor kartu kredit, PIN, atau kata sandi perbankan Anda.</li>
                <li><strong>Data teknis:</strong> alamat IP, jenis browser dan perangkat (user agent), serta catatan aktivitas akun seperti waktu masuk, yang kami gunakan untuk keamanan dan pencegahan penyalahgunaan.</li>
                <li><strong>Komunikasi:</strong> pesan yang Anda kirimkan kepada kami melalui WhatsApp, email, atau telepon.</li>
            </ul>
        </section>

        <section id="file-model">
            <h2>3. File Model 3D & Kerahasiaan Desain</h2>
            <p>
                Saat Anda mengunggah file di halaman 3D Models, file tersebut <strong>dianalisis langsung di perangkat Anda</strong>
                dan disimpan di penyimpanan browser Anda sendiri. File baru terkirim ke server kami ketika Anda menekan tombol untuk
                mengirim permintaan penawaran.
            </p>
            <p>
                File yang Anda kirimkan disimpan di penyimpanan privat yang tidak dapat diakses publik, dan hanya digunakan oleh tim kami
                untuk meninjau, menghitung penawaran, serta memproduksi pesanan Anda. Kami tidak akan mempublikasikan, menjual, atau
                menggunakan desain Anda untuk keperluan lain, termasuk sebagai materi promosi, tanpa izin tertulis dari Anda.
                Bila proyek Anda memerlukan perjanjian kerahasiaan (NDA), silakan hubungi kami sebelum mengirimkan file.
            </p>
        </section>

        <section id="penggunaan">
            <h2>4. Cara Kami Menggunakan Data</h2>
            <ul>
                <li>Membuat dan mengelola akun Anda.</li>
                <li>Meninjau file, menyusun estimasi dan penawaran harga, serta memproduksi dan mengirimkan pesanan.</li>
                <li>Memverifikasi pembayaran dan mengelola jadwal termin pembayaran.</li>
                <li>Mengirimkan informasi status penawaran, pengingat pembayaran, dan pemberitahuan penting lainnya melalui email, WhatsApp, atau notifikasi di dashboard.</li>
                <li>Menanggapi pertanyaan, konsultasi, maupun keluhan Anda.</li>
                <li>Menjaga keamanan akun dan website, termasuk mendeteksi serta mencegah penipuan dan penyalahgunaan.</li>
                <li>Memahami kebutuhan pelanggan dan meningkatkan kualitas layanan, termasuk mengukur efektivitas iklan kami.</li>
                <li>Memenuhi kewajiban hukum, perpajakan, dan administrasi yang berlaku.</li>
            </ul>
        </section>

        <section id="dasar">
            <h2>5. Dasar Pemrosesan</h2>
            <p>Kami memproses data pribadi Anda berdasarkan:</p>
            <ul>
                <li><strong>Persetujuan</strong> yang Anda berikan, misalnya saat membuat akun atau mengirim permintaan penawaran;</li>
                <li><strong>Pelaksanaan perjanjian</strong> antara Anda dan kami, yaitu pemrosesan pesanan dan pengiriman;</li>
                <li><strong>Pemenuhan kewajiban hukum</strong>, misalnya pencatatan transaksi untuk keperluan perpajakan; dan</li>
                <li><strong>Kepentingan yang sah</strong>, seperti menjaga keamanan sistem dan meningkatkan layanan, dengan tetap memperhatikan hak Anda.</li>
            </ul>
        </section>

        <section id="pihak-ketiga">
            <h2>6. Berbagi Data dengan Pihak Ketiga</h2>
            <p>
                Kami <strong>tidak menjual</strong> data pribadi Anda. Data hanya kami bagikan sebatas yang diperlukan kepada pihak berikut:
            </p>
            <ul>
                <li><strong>Penyedia infrastruktur</strong>, seperti layanan hosting dan email, yang menyimpan atau mengirimkan data atas nama kami.</li>
                <li><strong>Penyedia layanan pesan WhatsApp</strong>, untuk mengirimkan notifikasi status penawaran ke nomor yang Anda daftarkan.</li>
                <li><strong>Jasa ekspedisi</strong>, berupa nama, nomor telepon, dan alamat penerima untuk keperluan pengiriman.</li>
                <li><strong>Google</strong>, melalui Google Ads untuk mengukur efektivitas iklan, Google Fonts untuk menampilkan huruf, Google Maps untuk menampilkan peta lokasi di halaman About, dan Google Translate bila Anda memilih tampilan bahasa Inggris (EN).</li>
                <li><strong>Instansi berwenang</strong>, apabila diwajibkan oleh peraturan perundang-undangan atau perintah hukum yang sah.</li>
            </ul>
        </section>

        <section id="cookie">
            <h2>7. Cookie & Penyimpanan Browser</h2>
            <p>Website ini menggunakan cookie dan penyimpanan browser untuk keperluan berikut:</p>
            <ul>
                <li><strong>Esensial:</strong> menjaga sesi login dan melindungi formulir dari serangan (cookie sesi dan token keamanan). Tanpa cookie ini, Anda tidak dapat masuk ke akun.</li>
                <li><strong>Preferensi:</strong> mengingat pilihan bahasa (ID/EN), status pemberitahuan cookie, serta menyimpan file model yang Anda unggah di browser Anda sampai penawaran dikirim.</li>
                <li><strong>Pengukuran iklan:</strong> cookie Google Ads untuk mengetahui efektivitas iklan kami.</li>
            </ul>
            <p>
                Anda dapat menghapus atau memblokir cookie melalui pengaturan browser. Perlu diketahui, memblokir cookie esensial akan
                membuat beberapa fitur, seperti login dan permintaan penawaran, tidak dapat digunakan.
            </p>
        </section>

        <section id="keamanan">
            <h2>8. Penyimpanan & Keamanan Data</h2>
            <p>
                Kami menerapkan langkah-langkah pengamanan yang wajar, antara lain koneksi terenkripsi (HTTPS), penyimpanan kata sandi
                dalam bentuk hash, penyimpanan file model dan bukti pembayaran di area privat, serta pembatasan akses hanya bagi
                personel yang berwenang. Meski demikian, tidak ada sistem yang sepenuhnya bebas risiko; kami akan memberitahukan Anda
                sesuai ketentuan yang berlaku apabila terjadi kegagalan pelindungan data pribadi.
            </p>
            <p>
                Data disimpan selama akun Anda aktif atau selama diperlukan untuk tujuan sebagaimana dijelaskan dalam kebijakan ini,
                termasuk untuk memenuhi kewajiban hukum, perpajakan, dan penyelesaian sengketa. Setelah tidak lagi diperlukan, data akan
                dihapus atau dianonimkan.
            </p>
        </section>

        <section id="hak">
            <h2>9. Hak Anda</h2>
            <p>Sesuai peraturan perundang-undangan, Anda berhak untuk:</p>
            <ul>
                <li>memperoleh informasi dan mengakses salinan data pribadi Anda;</li>
                <li>memperbarui atau memperbaiki data yang tidak akurat, sebagian besar dapat Anda lakukan sendiri melalui menu Profil di dashboard;</li>
                <li>meminta penghapusan data pribadi Anda;</li>
                <li>menarik kembali persetujuan pemrosesan data;</li>
                <li>mengajukan keberatan atas pemrosesan tertentu; dan</li>
                <li>meminta data Anda dalam format yang umum digunakan.</li>
            </ul>
            <p>
                Untuk menggunakan hak tersebut, hubungi kami melalui kontak di bawah. Kami dapat meminta verifikasi identitas sebelum
                memproses permintaan Anda. Perlu diketahui, sebagian data transaksi tetap wajib kami simpan untuk memenuhi kewajiban hukum.
            </p>
        </section>

        <section id="anak">
            <h2>10. Pengguna di Bawah Umur</h2>
            <p>
                Layanan kami ditujukan bagi pengguna dewasa dan pelaku usaha. Pengguna yang belum dewasa hanya boleh menggunakan layanan
                kami dengan persetujuan dan pendampingan orang tua atau wali.
            </p>
        </section>

        <section id="perubahan">
            <h2>11. Perubahan Kebijakan</h2>
            <p>
                Kebijakan Privasi ini dapat kami perbarui dari waktu ke waktu. Perubahan akan diumumkan di halaman ini dengan tanggal
                pembaruan terbaru. Kami menyarankan Anda meninjau halaman ini secara berkala.
            </p>
        </section>

        <section id="kontak">
            <h2>12. Hubungi Kami</h2>
            <p>Untuk pertanyaan atau permintaan terkait data pribadi Anda, silakan hubungi:</p>
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
