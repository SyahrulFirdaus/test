# Video Hero Halaman Utama

Carousel pada jumbotron halaman utama memutar empat video pendek berikut.
Letakkan berkasnya di folder ini dengan nama persis seperti di bawah:

| Berkas | Isi yang disarankan |
| --- | --- |
| `fdm-printing.mp4` | Proses FDM: filamen diekstrusi lapis demi lapis |
| `resin-printing.mp4` | Proses resin/SLA: part terangkat dari vat resin |
| `post-processing.mp4` | Finishing: sanding, primer, painting |
| `workshop.mp4` | Suasana workshop dengan beberapa mesin berjalan |

Saran teknis:

- Format **MP4 (H.264 + AAC)** agar dapat diputar seluruh browser.
- Rasio **potret 4:5** atau **4:3**, resolusi 720p sudah cukup.
- Durasi 6–12 detik, dibuat berulang mulus (looping).
- Ukuran di bawah 3 MB per berkas supaya hero tetap cepat dimuat.
- Video diputar **tanpa suara** dan otomatis berulang.

Selama berkasnya belum ada, setiap slide menampilkan gambar poster dari
`public/images/photos/` sehingga tampilan hero tetap utuh. Gambar poster diatur
pada array `$heroSlides` di `resources/views/pages/home.blade.php`.
