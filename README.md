# NUSAMA3D — Sistem Manajemen Penawaran 3D Printing

Website perusahaan jasa **3D Printing / Additive Manufacturing** sekaligus sistem manajemen
penawarannya, dibangun dengan Laravel + Blade + Tailwind CSS, dengan warna identitas utama
**`#95271D`**.

Selain company profile dan Pre-Print Analyzer (halaman 3D Models), sistem ini menyediakan akun
pelanggan, dashboard penawaran untuk pelanggan maupun admin, notifikasi dua arah, dan alur
pembatalan berjenjang.

---

## Stack

| Bagian     | Teknologi                                              |
| ---------- | ------------------------------------------------------ |
| Framework  | Laravel 12 (PHP ^8.2)                                  |
| Database   | MySQL / MariaDB — Eloquent ORM                         |
| Templating | Blade (layout, partial, dan komponen reusable)         |
| CSS        | Tailwind CSS v4 via Vite                               |
| Animasi    | AOS (Animate On Scroll) + CSS animation                |
| Viewer 3D  | Three.js (STLLoader, OBJLoader, OrbitControls)         |

> **Catatan versi:** permintaan awal menyebut Laravel 13, namun Laravel 13 mensyaratkan **PHP ^8.3**
> sedangkan PHP yang terpasang di mesin ini adalah **8.2.12** (XAMPP). Project dibangun di atas
> **Laravel 12** yang mendukung PHP ^8.2. Struktur folder, routing, controller, dan Blade-nya identik;
> untuk naik ke Laravel 13 cukup upgrade PHP ke 8.3+ lalu ubah `laravel/framework` di `composer.json`
> menjadi `^13.0` dan jalankan `composer update`.

---

## Menjalankan Project

Prasyarat: PHP 8.2+, Composer, Node.js 18+, dan MySQL/MariaDB yang sedang berjalan.

```bash
composer install
```

```bash
npm install
```

Salin konfigurasi environment lalu buat application key:

```bash
cp .env.example .env
```

```bash
php artisan key:generate
```

Sesuaikan kredensial database pada `.env` (default sudah cocok dengan XAMPP):

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nusaprint3d
DB_USERNAME=root
DB_PASSWORD=
```

Buat databasenya, lalu jalankan migrasi beserta data awal:

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS nusaprint3d CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

```bash
php artisan migrate --seed
```

Build asset frontend:

```bash
npm run build
```

Jalankan server:

```bash
php artisan serve
```

Website dapat diakses di **http://127.0.0.1:8000**.

Selama pengembangan, jalankan Vite dalam mode watch di terminal terpisah agar perubahan
Blade/CSS/JS langsung tercermin:

```bash
npm run dev
```

### Batas unggah pada php.ini

Satu penawaran dirancang memuat **25 file 3D dengan ukuran maksimal 300 MB per file**
(`limits.max_models_per_quotation` dan `limits.max_file_size_mb` di `config/printing.php`).
Batas itu hanya berlaku bila `php.ini` mengizinkannya — nilai bawaan XAMPP jauh lebih kecil
(40 MB dan 20 berkas), dan aplikasi tidak dapat melampaui batas PHP.

Setel `php.ini` (XAMPP: `C:\xampp\php\php.ini`) menjadi:

```
upload_max_filesize = 300M
post_max_size = 1024M
max_file_uploads = 30
memory_limit = 512M
max_execution_time = 300
```

Lalu jalankan ulang Apache/`php artisan serve`. `App\Support\UploadLimit` selalu memakai nilai
**terkecil** di antara config aplikasi dan `php.ini`, sehingga halaman 3D Models maupun dashboard
menolak berkas kelewat besar dengan pesan yang jelas — bukan berakhir sebagai error 413/419.
`post_max_size` membatasi **ukuran gabungan** seluruh berkas dalam satu permintaan, jadi nilainya
perlu jauh lebih besar daripada batas per berkas.

---

## Struktur Halaman

| Route         | Nama Route     | Controller               | Isi                                                            |
| ------------- | -------------- | ------------------------ | -------------------------------------------------------------- |
| `/`           | `home`         | `HomeController`         | Hero, profil singkat, sorotan layanan & teknologi, alur kerja, CTA |
| `/services`   | `services`     | `ServiceController`      | 6 layanan dalam bentuk card + uraian lengkap tiap layanan       |
| `/technologies` | `technologies` | `TechnologyController` | FDM, SLA, MJF, SLM — penjelasan, kelebihan, aplikasi, tabel perbandingan |
| `/3d-models` | `models`       | `ModelCheckController`   | Pre-Print Analyzer: upload, daftar model, estimasi, penawaran     |
| `/3d-models/viewer` | `models.viewer` | `ModelCheckController` | Viewer 3D satu model beserta seluruh analisis & simulasinya |
| `/about`      | `about`        | `AboutController`        | Profil perusahaan, visi & misi, keunggulan, informasi kontak    |
| `/sitemap.xml`| `sitemap`      | `SitemapController`      | Sitemap XML untuk mesin pencari                                 |

> URL lama `/cek-barang` dan `/cek-barang/viewer` dialihkan permanen ke alamat barunya, sehingga
> tautan yang terlanjur tersebar tetap sampai ke tujuannya.

Endpoint & halaman pendukung:

| Route | Nama Route | Keterangan |
| --- | --- | --- |
| `POST /3d-models/penawaran` | `quotations.store` | Menerima permintaan penawaran beserta file model (**wajib login**) |
| `/tracking` | `tracking.index` | Formulir pencarian nomor tracking |
| `/tracking/{nomor}` | `tracking.show` | Halaman Tracking Penawaran (tanpa login) |
| `/tracking/{nomor}/bukti-penawaran` | `tracking.document` | Unduh Bukti Penawaran (PDF) |

Akun pelanggan:

| Route | Nama Route | Keterangan |
| --- | --- | --- |
| `/login` · `/register` | `login` · `register` | Masuk & daftar akun pelanggan |
| `/lupa-password` · `/reset-password/{token}` | `password.request` · `password.reset` | Alur Lupa Password |
| `/dashboard` | `dashboard` | Ringkasan dashboard pelanggan |
| `/dashboard/penawaran` | `dashboard.quotations.index` | Penawaran Saya |
| `/dashboard/penawaran/{id}` | `dashboard.quotations.show` | Detail penawaran |
| `/dashboard/penawaran/{id}/ubah` | `dashboard.quotations.edit` | Ubah penawaran (selama Menunggu Review) |
| `POST /dashboard/penawaran/{id}/pembatalan` | `dashboard.quotations.cancel` | Batalkan / ajukan pembatalan |
| `/dashboard/notifikasi` | `dashboard.notifications.index` | Notifikasi pelanggan |
| `/dashboard/profil` · `/dashboard/ganti-password` | `dashboard.profile.edit` · `dashboard.password.edit` | Profil & ganti password |

Dashboard admin:

| Route | Nama Route | Keterangan |
| --- | --- | --- |
| `/admin/login` | `admin.login` | Halaman masuk admin |
| `/admin` | `admin.dashboard` | Dashboard statistik |
| `/admin/permintaan` | `admin.quotations.index` | Daftar permintaan penawaran |
| `/admin/permintaan/{id}` | `admin.quotations.show` | Detail permintaan |
| `/admin/permintaan/{id}/unduh` | `admin.quotations.download` | Unduh file STL/OBJ |
| `POST /admin/permintaan/{id}/pembatalan/setujui` | `admin.quotations.cancellation.approve` | Setujui pembatalan |
| `POST /admin/permintaan/{id}/pembatalan/tolak` | `admin.quotations.cancellation.reject` | Tolak pembatalan |
| `/admin/pengguna` · `/admin/pengguna/{id}` | `admin.users.index` · `admin.users.show` | Manajemen user |
| `/admin/notifikasi` | `admin.notifications.index` | Notifikasi admin |
| `/admin/profil` · `/admin/ganti-password` | `admin.profile.edit` · `admin.password.edit` | Profil & ganti password |

---

## Struktur Folder Penting

```
app/
├── Http/
│   ├── Controllers/       # Satu controller per halaman + Auth/, Dashboard/, Admin/
│   ├── Middleware/        # EnsureUserIsAdmin, EnsureUserIsCustomer
│   └── Requests/          # StoreQuotationRequest, RegisterRequest, UpdateProfileRequest
├── Models/                # User, Service, Technology, CompanyProfile,
│                          # QuotationRequest, QuotationItem, QuotationHistory
├── Notifications/         # NewQuotationSubmitted, QuotationStatusUpdated,
│                          # CancellationRequested, CancellationDecided
├── Providers/             # View composer: profil perusahaan & menu footer
├── Services/              # PrintEstimator (estimasi), MeshInspector (baca STL/OBJ di server)
└── Support/               # QuotationStatus (alur status), UploadLimit (batas unggah),
                           # Finishing, MaterialColor, InfillPattern, PrintResolution, Printer

config/printing.php        # Harga, densitas, tarif mesin, build volume, batas analisis, alur status

database/
├── migrations/            # services, technologies, company_profiles, quotation_requests,
│                          # quotation_items, quotation_histories, kolom profil & role pada
│                          # users, kolom pembatalan, notifications
└── seeders/               # Konten awal seluruh halaman + akun admin

resources/
├── css/app.css            # Design token (@theme), komponen, utility kustom
├── js/
│   ├── app.js             # Entry global: AOS, navbar, counter, smooth scroll
│   ├── model-viewer.js    # Entry halaman 3D Models: unggah & daftar model
│   ├── model-detail.js    # Entry halaman Viewer 3D satu model
│   ├── dashboard.js       # Entry dashboard: sidebar, lonceng, popup notifikasi
│   └── modules/           # navbar, counters, smooth-scroll, model-workspace,
│                          # printer-card, shared-renderer, build-plate,
│                          # mesh-analysis, mesh-paint, support-builder,
│                          # print-estimator, quotation-form,
│                          # model-store (IndexedDB), model-record, model-spec,
│                          # thumbnail
└── views/
    ├── layouts/           # app (publik), auth (login/register), dashboard (admin & user)
    ├── components/model-check/  # printer-card: card viewer & analisis, dipakai
    │                            # halaman daftar maupun halaman viewer
    ├── auth/              # login, register, forgot-password, reset-password
    ├── dashboard/         # Dashboard pelanggan: index, quotations/, notifikasi, profil, password
    ├── admin/             # Login, dashboard statistik, permintaan, pengguna, notifikasi, profil
    ├── partials/          # navbar, footer, seo, scroll-top, dashboard/ (notifikasi, profil, password)
    ├── components/        # section-heading, service-card, technology-block,
    │   ├── icons/         # cta-band, page-hero, logo-mark, dan ikon SVG
    │   └── admin/         # analysis-badge
    └── pages/             # home, services, technologies, model-check, about

public/images/             # Ilustrasi SVG layanan, teknologi, hero, OG cover
public/images/photos/      # Foto stok berlisensi bebas — GANTI dengan foto asli Anda
public/images/clients/     # Logo klien, dipotong dari materi "trusted by"
storage/app/private/quotations/   # Berkas model yang diunggah (tidak public)
```

---

## Halaman "3D Models" — Pre-Print Analyzer

Halaman ini menerima file **`.stl`** (biner maupun ASCII) dan **`.obj`** melalui **drag & drop**
maupun tombol **Upload File**, lalu memprosesnya menjadi pratinjau, analisis, dan estimasi.

**Beberapa file dapat diunggah sekaligus** — pelanggan yang ingin mencetak beberapa komponen tidak
perlu membuat penawaran satu per satu. Lihat [Beberapa model dalam satu penawaran](#beberapa-model-dalam-satu-penawaran).

### Daftar model & viewer di tab terpisah

Halaman 3D Models **tidak menampilkan viewer 3D secara langsung**. Setiap file yang diunggah
langsung dibaca, dianalisis, dan muncul sebagai **kartu ringkas** berisi:

| Isi kartu | Keterangan |
| --- | --- |
| Thumbnail | Gambar yang dirender dari model itu sendiri — bukan ikon file |
| Nama file | Beserta format dan ukurannya |
| Dimensi | X × Y × Z dalam milimeter, mengikuti orientasi dan skala saat ini |
| Volume | Volume model dalam cm³ |
| Berat estimasi | Berat seluruh unit, termasuk support bila aktif |
| Spesifikasi | Teknologi, material, warna, dan finishing yang dipilih |
| Quantity | Pengatur jumlah dengan tombol − / +, minimal 1 pcs |
| Estimasi harga | Total biaya model tersebut |
| Status analisis | Ready to Print / Perlu Perbaikan / Not Printable |
| Tombol **Edit Specification** | Membuka modal pengaturan teknologi, material, warna, finishing, dan jumlah |
| Tombol **Lihat 3D** | Membuka viewer di tab baru; thumbnailnya juga dapat diklik |

Dengan begitu halaman ini tetap ringan meski memuat puluhan model — tidak ada puluhan context WebGL
yang harus digambar bersamaan.

**Thumbnail dibuat otomatis dari modelnya.** Saat file diunggah, card mesin dikloning ke tempat
tersembunyi di luar layar, dipakai sekali untuk mengukur geometri, menjalankan analisis kelayakan,
menghitung estimasi, dan **memotret modelnya** dari sudut tiga perempat lewat
`resources/js/modules/thumbnail.js` — lalu salinan cardnya langsung dibuang. Model gear menghasilkan
gambar gear, model casing menghasilkan casing.

**Viewer 3D berada di `/3d-models/viewer?model={id}`.** Halaman itu berfungsi murni sebagai
**viewer dan alat analisis** — bukan tempat mengubah spesifikasi produksi. Yang tersedia di sana:
rotate, zoom, pan, reset camera, preset sudut pandang, build plate, bounding box, support preview,
analisis overhang, wall thickness, orientasi & skala model, serta informasi model lengkap (dimensi,
volume, luas permukaan, jumlah verteks/segitiga) beserta ringkasan estimasi yang bersifat baca saja.

Panel **Pilihan Produksi** dan **Pengaturan Printing — Resolusi & Infill** sudah tidak ada di
halaman ini. Seluruh pengaturan produksi dipusatkan pada **Edit Specification** di halaman 3D
Models, sehingga tidak ada pengaturan yang sama di dua tempat.

Markupnya tidak diduplikasi: keduanya memakai satu komponen Blade
`resources/views/components/model-check/printer-card.blade.php` — pada halaman daftar sebagai isi
`<template>` yang dikloning, pada halaman viewer dirender langsung.

**Bagaimana model sampai ke tab baru?** Tab baru tidak dapat mewarisi objek `File` dari tab
sebelumnya, jadi berkasnya dititipkan di **IndexedDB** (`resources/js/modules/model-store.js`) —
masih di perangkat pengguna, masih tidak pernah menyentuh server sampai penawaran dikirim. Id model
diteruskan lewat query string, dan berkasnya dibaca kembali dari sana.

Karena tersimpan di browser, daftar model **bertahan saat halaman dimuat ulang**. Bersihkan lewat
tombol **Hapus Semua Model** atau tombol Hapus pada tiap kartu.

Perubahan pengaturan di tab viewer disimpan kembali beserta thumbnail barunya, lalu disiarkan lewat
`BroadcastChannel` sehingga daftar di tab lain ikut menyesuaikan tanpa dimuat ulang.

### Viewer 3D

Seluruh fitur di bawah ini berjalan pada halaman viewer (`/3d-models/viewer?model={id}`), dibuka
dari daftar model lewat tombol **Lihat 3D** atau dengan mengklik thumbnailnya.

- **Rotate** (klik kiri + tarik / satu jari), **Zoom** (scroll / cubit), **Pan** (klik kanan + tarik / dua jari)
- **Reset Camera** — kembali ke framing awal
- Mode tampilan: **Solid**, **Wireframe**, **Transparan**
- Toggle **Show Grid**, **Show Axis**, **Auto-rotate**, **Fullscreen**, dan **Hapus model**
- Preset sudut pandang: **Front, Back, Left, Right, Top, Bottom, Isometric**
- Indikator **loading** saat membaca file, memproses geometri, dan menganalisis
- Pesan kesalahan informatif: format tidak didukung, file kosong, melebihi batas ukuran,
  file rusak, dan browser tanpa dukungan WebGL

### Orientasi model

Berbeda dari kontrol kamera di atas, panel **Orientasi Model** mengubah posisi objeknya sendiri —
persis seperti mengatur posisi part di atas meja cetak:

- Rotasi bebas pada sumbu **X, Y, Z** lewat slider, atau bertahap dengan tombol ±15° / ±90°
- **Putar dengan Drag** — tarik langsung objeknya di kanvas (drag kiri memutar objek, bukan kamera)
- **Balik / cerminkan** pada masing-masing sumbu
- **Reset Orientasi** untuk kembali ke posisi asli file

Model selalu didudukkan ulang tepat di atas grid setelah diputar, dan **dimensi, bounding box,
serta pemeriksaan area cetak ikut dihitung ulang** — karena orientasi memang menentukan apakah
part masih muat di mesin. Bila part tidak muat pada orientasi sekarang tetapi muat bila diputar,
pesan analisisnya menyebutkan hal itu secara eksplisit.

Volume, luas permukaan, dan pemeriksaan topologi (watertight, lubang, non-manifold, arah normal)
sengaja dihitung dari geometri asli sehingga **tidak berubah** saat model diputar atau dibalik.
Orientasi yang dipilih ikut terkirim bersama permintaan penawaran dan tampil di dashboard admin.

### Informasi model

Nama file, format, ukuran file, jumlah vertex, jumlah face/triangle, dimensi P × L × T,
bounding box, luas permukaan, dan volume model. Volume ditandai `±` bila mesh belum tertutup,
karena pada kondisi itu angkanya hanya perkiraan.

### Analisis kelayakan cetak

Dihitung langsung dari buffer geometri di browser (`resources/js/modules/mesh-analysis.js`):

| Pemeriksaan | Cara kerja |
| --- | --- |
| Mesh tertutup (watertight) | Vertex di-*weld* berdasarkan posisi, lalu dicek apakah ada tepi batas |
| Lubang pada permukaan | Tepi yang hanya dipakai satu muka; jumlah lubang dihitung per loop batas |
| Non-manifold edge | Tepi yang dipakai lebih dari dua muka |
| Arah normal terbalik | Winding tidak konsisten antar-muka, dan tanda volume bertanda |
| Ukuran model | Sisi terkecil dibandingkan batas di `config/printing.php` |
| Area cetak | Bounding box dibandingkan build volume teknologi yang dipilih |

Hasilnya diringkas menjadi 🟢 **Ready to Print**, 🟡 **Need Improvement**, atau 🔴 **Not Printable**,
masing-masing disertai penjelasan singkat. Model dengan segitiga di atas
`limits.max_triangles_full_analysis` hanya dihitung volume & bounding box-nya agar browser tetap responsif.

### Teknologi, material, dan estimasi

Dropdown teknologi (FDM / SLA / MJF / SLM) menampilkan deskripsi singkat, dan daftar materialnya
menyesuaikan otomatis. Estimasi berat, volume material, waktu, dan biaya dihitung ulang setiap kali
teknologi, material, atau jumlah cetak diubah.

### Resolusi (Layer Height)

Panel **Pengaturan Printing** menyediakan empat pilihan resolusi bergaya kartu, seperti pilihan
kualitas pada Ultimaker Cura. Hanya satu yang aktif dalam satu waktu (radio button), default
**0,25 mm (Normal)**.

| Resolusi | Kualitas | Pengali waktu | Contoh kubus 60 mm, FDM/PLA |
| --- | --- | --- | --- |
| 0,05 mm — Ultra Fine | Sangat Tinggi | 2,0× | 12 jam 56 menit · Rp 289.500 |
| 0,10 mm — Fine | Tinggi | 1,5× | 9 jam 36 menit · Rp 247.500 |
| 0,25 mm — Normal | Normal | 1,0× | 6 jam 23 menit · Rp 206.500 |
| 0,50 mm — Draft | Draft | 0,5× | 3 jam 15 menit · Rp 166.000 |

Mengganti resolusi memperbarui **waktu, volume material, berat, biaya, dan tingkat kualitas**
secara real-time tanpa reload. Seluruh angkanya ada di `resolutions` pada `config/printing.php`:

```
volume_material = volume_model x fill_factor x material_multiplier
waktu_cetak     = volume_material / (throughput x kecepatan_printer) x time_multiplier
```

Pengali waktu mengikuti hubungan terbalik antara tebal lapisan dan jumlah lapisan yang dicetak
(0,25 mm sebagai acuan). Pengali material sengaja kecil — tebal lapisan hampir tidak mengubah
volume bahan.

> **Rentang lapisan berbeda tiap teknologi.** Empat pilihan tetap ditawarkan untuk semua teknologi
> sesuai permintaan, tetapi bila pilihan berada di luar rentang mesin (`layer_height_range` per
> teknologi), muncul peringatan kuning — misalnya *"Tebal lapisan MJF tetap 0,08 mm"* atau
> *"Tebal lapisan SLM 0,02 – 0,06 mm"*. Pilihan tidak diblokir; pengguna hanya diberi tahu bahwa
> tim akan menyesuaikannya saat produksi.

Resolusi yang dipilih ikut tersimpan pada permintaan penawaran (`resolution` dan `layer_height_mm`)
serta tampil di dokumen PDF, halaman tracking, dan dashboard admin.

### Satu printer untuk satu model

Setiap berkas yang diunggah langsung mendapat **mesin printer dan build plate-nya sendiri** —
**1 printer = 1 build plate = 1 objek**. Mengunggah tiga file menghasilkan Printer 1, Printer 2,
dan Printer 3; file keempat otomatis membuat Printer 4. Tidak pernah ada dua model dalam satu
build plate.

Setiap printer tampil sebagai card tersendiri berisi viewer 3D, build plate, informasi build
volume, informasi model, pengaturan produksi, analisis kelayakan, dan estimasi biayanya masing-
masing. Pengaturan pada satu card sama sekali tidak menyentuh card lain.

> **Satu context WebGL untuk semua viewer.** Sepuluh card berarti sepuluh viewer, sedangkan
> browser hanya mengizinkan belasan context WebGL sebelum mencabut yang paling lama. Karena itu
> `resources/js/modules/shared-renderer.js` memakai pola *multiple elements* dari Three.js: satu
> renderer tersembunyi menggambar tiap scene bergantian lalu menyalin hasilnya ke kanvas 2D
> masing-masing card. Hanya viewer yang terlihat di layar dan memang berubah yang digambar ulang.

### Build plate

Setiap card menggambar **Virtual Build Plate** seukuran mesin yang dipilih, lengkap dengan meja
cetak berkisi, kotak batas volume cetak, sumbu X/Y/Z, dan penanda titik origin (0,0,0) di tengah
meja. Karena satu plate hanya berisi satu objek, modelnya selalu berdiri tepat di titik origin.
Tombol **Fokus Model** mendekatkan kamera ke modelnya, **Reset Camera** kembali membingkai
seluruh build plate.

### Mesin printer

**Pemilihan mesin tidak lagi ditampilkan kepada pelanggan.** Setiap model memakai mesin bawaan
(`printers.default` di `config/printing.php`, saat ini Creality Ender 3) — penentuan mesin yang
benar-benar dipakai menjadi keputusan tim produksi, bukan pelanggan.

Daftar mesinnya sendiri tetap ada di config beserta pengaruhnya pada estimasi:

| Printer | Build volume | Kecepatan | Tarif |
| --- | --- | --- | --- |
| Creality Ender 3 | 220 × 220 × 250 mm | 0,85× | 0,90× |
| Bambu Lab X1 Carbon | 256 × 256 × 256 mm | 1,75× | 1,15× |
| Prusa MK4 | 250 × 210 × 220 mm | 1,30× | 1,05× |
| Anycubic Kobra | 220 × 220 × 250 mm | 0,95× | 0,90× |
| Custom | diisi sendiri (Width / Depth / Height) | 1,00× | 1,00× |

Ukuran build plate, validasi ukuran, dan peringatan tetap berjalan seperti sebelumnya: bila model
keluar batas, kotak volume berubah merah, bagian yang melewati batas digambar sebagai **kotak merah
transparan**, dan muncul peringatan **⚠ Object exceeds build volume**. Mengganti mesin bawaan cukup
dilakukan lewat `printers.default` di config.

Panel **Informasi Build Volume** pada tiap card menampilkan ukuran mesin, ukuran model, dan
persentase pemakaian area cetaknya beserta bar indikator.

### Analisis Overhang & Wall Thickness

Tiga mode tampilan pada toolbar viewer: **Normal**, **Overhang**, dan **Wall Thickness**. Keduanya
mewarnai model lewat atribut warna per vertex, sehingga dapat dilepas kembali tanpa menyentuh
geometri — mematikannya mengembalikan warna material.

- **Overhang** — sudut tiap muka diukur dari bidang tegak (dinding tegak 0°, langit-langit
  mendatar 90°): hijau di bawah 45°, kuning 45°–60°, merah di atas 60°.
- **Wall Thickness** — dari tiap muka ditembakkan sinar ke dalam model, jaraknya sampai permukaan
  seberang adalah tebal dindingnya. Hijau bila memenuhi `min_wall_thickness_mm` teknologi
  (FDM 1,2 mm, SLA & MJF 0,8 mm, SLM 0,5 mm), merah bila lebih tipis.

Agar tetap seketika pada model rapat, penembakan sinar dipercepat dengan kisi seragam di
`resources/js/modules/mesh-paint.js`; model di atas `max_triangles` dilewati dan model di atas
`max_samples` diukur dari titik sampel — keduanya disampaikan lewat legenda di viewer.

### Scale Model

Panel **Scale Model** mengatur skala 10–400% lewat isian angka, slider, tombol +/−, atau preset
50/80/100/120/150%. Volume berubah pangkat tiga terhadap skala, jadi dimensi, volume, berat,
waktu, dan biaya dihitung ulang seketika. Berkas asli tidak diubah — skalanya dicatat sebagai
permintaan produksi (`scale_percent`).

### Infill

Kepadatan **10 / 20 / 40 / 60 / 80 / 100%** dengan empat pola: **Grid** (acuan), **Cubic**,
**Triangle**, dan **Gyroid**, masing-masing dengan pengali material dan waktunya sendiri.
Pengali pola diberlakukan sebanding kepadatannya, jadi pada infill 0% pola apa pun tidak
berpengaruh. Karena infill hanya mengisi rongga di dalam dinding, teknologi yang mengeras padat
(SLA) menampilkan keterangan bahwa pilihan ini tidak mengubah pemakaian materialnya.

### Hollow Model (khusus SLA)

Muncul hanya bila teknologi SLA dipilih. Saat aktif, bagian dalam part dikosongkan sehingga
menyisakan cangkang setebal **Wall Thickness**, dikurangi **Drain Hole** yang menembusnya
(diameter dan posisinya dapat dipilih). Volume cangkang diperkirakan dari luas permukaan model
dikali tebal dinding dan tidak pernah melebihi volume padatnya. Penghematan resin ditampilkan
langsung, mis. *"Menghemat 90,44 cm³ resin (75% dari volume padat) pada dinding 2,0 mm."*

### Label kelayakan cetak & harga sebelum login

**Label evaluasi tidak ditampilkan kepada pelanggan.** Status kelayakan cetak (Ready to Print /
Need Improvement / Not Printable) beserta daftar pemeriksaannya tetap dihitung di browser dan ikut
terkirim bersama penawaran — tetapi hanya muncul di **dashboard admin**, tempat engineer
menindaklanjutinya. Halaman 3D Models dan halaman viewer hanya menampilkan informasi teknis:
dimensi, volume, luas permukaan, berat, build volume, overhang, wall thickness, dan support preview.

**Harga hanya untuk pengguna yang sudah masuk.** Pengunjung tanpa akun tetap dapat mengunggah file,
melihat thumbnail dan pratinjau 3D, membaca dimensi/volume/berat, memakai seluruh alat analisis, dan
mengatur spesifikasi lewat Edit Specification. Yang ditahan hanya angka komersialnya — estimasi
harga per model, total biaya, kolom biaya pada ringkasan, dan tombol Minta Penawaran — diganti
ajakan **"Login untuk melihat estimasi harga dan membuat penawaran."** beserta tombol **Sign In**
dan **Daftar Akun**.

Karena model tersimpan di IndexedDB pada browser pengunjung, **tidak perlu mengunggah ulang setelah
masuk**: begitu kembali ke halaman 3D Models, model yang sama muncul lagi lengkap dengan harganya.

### Quantity & Edit Specification

Setiap kartu pada daftar model punya pengatur **Quantity** sendiri: kotak angka dengan tombol
**−** dan **+**, minimal 1 pcs. Menaikkan jumlah langsung memperbarui berat, waktu produksi, harga
model, dan ringkasan total penawaran — tanpa halaman dimuat ulang.

Tombol **Edit Specification** membuka modal berisi seluruh pilihan model tersebut:

| Pengaturan | Isi |
| --- | --- |
| Teknologi Printing | FDM, SLA, MJF, SLM |
| Material | Menyesuaikan teknologi — FDM: PLA/ABS/PETG/TPU, SLA: Standard/Tough/Flexible/Clear Resin, MJF: PA12/PA11, SLM: Stainless Steel/Aluminum/Titanium |
| Warna Material | Menyesuaikan material — Clear Resin hanya Bening, part logam hanya Natural Logam |
| Finishing | Tanpa Finishing, Sanding, Primer, Painting, Polishing |
| Quantity | Sama seperti pada kartu, lengkap dengan tombol − / + |
| Support Structure | Material penopang; otomatis terkunci pada teknologi yang tidak memerlukannya (MJF) |
| Hollow Model | Hanya tampil untuk teknologi yang mengizinkannya (SLA), lengkap dengan tebal dinding |

Modal menampilkan pratinjau **berat, waktu, dan harga** yang ikut berubah setiap kali pilihan
disentuh, jadi dampaknya terlihat sebelum disimpan. Setelah disimpan, kartu menampilkan
**ringkasan spesifikasi** (Teknologi, Material, Warna, Finishing, Quantity) sehingga pengguna tidak
perlu membuka modalnya lagi hanya untuk memeriksa.

Perubahan hanya berlaku pada model yang disunting — model lain tidak tersentuh sama sekali, karena
perhitungannya memakai data milik model itu saja.

**Kenapa terasa seketika?** Mengubah spesifikasi tidak menyentuh geometri, jadi tidak ada berkas
yang perlu dibaca ulang. `resources/js/modules/model-spec.js` menghitung ulang dari angka yang sudah
tersimpan (volume geometri, luas permukaan, dimensi, volume support terukur) memakai rumus yang sama
persis dengan viewer 3D maupun server — sehingga angka pada daftar, viewer, dan penawaran tidak
pernah berbeda.

### Finishing

Komponen biaya **Finishing** sudah ada sejak awal dan mewakili pembersihan dasar setiap part. Sejak
pilihan finishing tersedia, komponen itu dikalikan sesuai pilihannya dan menambah waktu pengerjaan
per unit — seluruhnya diatur `finishing.options` di `config/printing.php`:

| Pilihan | Pengali biaya | Tambahan waktu / unit |
| --- | --- | --- |
| Tanpa Finishing | 1,0 | — |
| Sanding | 1,8 | 15 menit |
| Primer | 2,4 | 24 menit |
| Polishing | 2,9 | 36 menit |
| Painting | 3,4 | 45 menit |

Pengali **1,0** pada "Tanpa Finishing" dipilih dengan sengaja: part yang tidak meminta finishing
tambahan berharga sama persis seperti sebelum fitur ini ada, sehingga penawaran lama tidak berubah
nilainya. Waktu finishing dihitung di luar jam mesin karena memang bukan pekerjaan mesin.

### Simulasi warna material

Warna material — Putih, Hitam, Merah, Biru, Abu-abu, Bening, dan Natural Logam — mengubah tampilan
model di viewer seketika. Murni simulasi visual: berkas model tidak diubah, tetapi pilihannya ikut
terkirim sebagai preferensi produksi.

Pilihan warna **menyesuaikan materialnya**, diatur lewat kunci `colors` pada tiap material di
`config/printing.php`. Bila material berganti dan warna yang sedang dipilih tidak tersedia, warna
terdekat yang memang ada dipakai — baik di browser maupun saat server menyimpan penawarannya
(`App\Support\MaterialColor::resolveForMaterial()`).

### Panel Estimasi Printing

Panel estimasi sengaja dibuat ringkas dan hanya memuat angka yang benar-benar dibaca pelanggan:

**Teknologi Printing · Material · Support Structure · Jumlah · Volume Material · Berat Model ·
Berat Support · Total Berat · Estimasi Waktu Printing · Estimasi Harga**

Mesin printer, resolusi, dan infill **tidak lagi ikut ditampilkan di panel ini** — ketiganya tetap
dapat diatur pada panel Pilihan Produksi dan Pengaturan Printing, dan tetap memengaruhi hasil
perhitungan seperti sebelumnya.

Seluruh nilai diperbarui otomatis saat pengguna mengubah printer, material, layer height, infill,
support, skala, finishing, jumlah, atau hollow model.

### Biaya: satu angka Estimasi Harga

Rincian biaya per komponen (Material, Waktu Printing, Support Structure, Finishing, Quality Control)
**tidak lagi ditampilkan kepada pelanggan** — tidak di halaman 3D Models, viewer, halaman tracking,
maupun PDF Bukti Penawaran. Yang tampil hanya satu angka: **Estimasi Harga**.

Perhitungan komponennya sendiri tetap berjalan di balik layar, karena total memang dihitung dari
penjumlahan komponen — bukan sebaliknya — dan hasilnya tetap tersimpan pada `cost_breakdown` di
`quotation_items` maupun `quotation_requests`. Dashboard admin masih menampilkannya sebagai alat
bantu penetapan harga; hapus bloknya di `resources/views/admin/quotations/show.blade.php` bila
memang tidak diperlukan.

### Support structure

Checkbox **Tambahkan Support Structure** (default OFF) mensimulasikan kebutuhan material penopang.
Saat diaktifkan, panel estimasi menampilkan rincian **Berat Model / Berat Support / Total Berat**,
dan waktu serta biaya ikut naik karena support juga tercetak. Mematikannya kembali mengembalikan
berat support ke 0 gram dan seluruh angka ke nilai semula — semuanya real-time, tanpa reload.

**Support juga tampil di viewer.** Saat checkbox aktif, struktur support dibentuk sebagai objek
Three.js tersendiri (`resources/js/modules/support-builder.js`) dan digambar biru muda transparan,
mengikuti konsep Ultimaker Cura:

1. Setiap muka mesh diperiksa arah normalnya pada orientasi yang sedang dipilih. Muka yang
   menggantung lebih dari `overhang_angle_deg` (default 45°, sama seperti Cura) ditandai.
2. Muka-muka tersebut **dirasterisasi** ke kisi pada bidang meja — bukan disampel beberapa titik —
   sehingga bidang overhang datar terisi merata berapa pun jumlah segitiganya. Tiap sel menyimpan
   titik overhang terendah.
3. Dari titik itu ditembakkan sinar ke bawah. Bila mengenai permukaan model, pilar tumbuh dari
   permukaan tersebut; bila tidak, pilar tumbuh dari meja cetak — sama seperti support Cura yang
   dapat berdiri di atas model.
4. Seluruh pilar digambar sebagai satu `InstancedMesh` beserta pelat dasar tipis, di dalam
   `THREE.Group` terpisah penuh dari mesh model.

Support selalu tumbuh tegak dari meja, jadi grupnya menjadi anak scene — bukan anak pivot model —
dan **dibentuk ulang setiap kali orientasi berubah**. Kamera memutar keduanya bersamaan karena
berada di scene yang sama. Tombol **Support** pada toolbar menyembunyikan/menampilkannya tanpa
mengubah perhitungan.

Karena strukturnya benar-benar dibentuk, **volume support diukur dari geometri itu** dan itulah yang
dipakai untuk estimasi berat — jadi angkanya sesuai dengan yang terlihat. Bila pada suatu orientasi
tidak ada overhang yang perlu ditopang, berat support menjadi 0 gram dan viewer memberi tahu hal itu,
bukan mengganti dengan angka perkiraan.

Rumus simulasi di bawah hanya menjadi cadangan bila pengukuran tidak tersedia. Seluruh parameter ada
di bagian `support` pada `config/printing.php`:

```
aspek        = tinggi / sisi tapak terpanjang
pengali      = 1 + min(aspek, max_aspect) x height_influence
volume kasar = volume_model x support_volume_factor x pengali x pengali_jenis
volume bahan = volume kasar x infill
berat        = volume bahan x densitas material
```

Faktor kelangsingan membuat part tinggi-langsing menuntut support lebih banyak daripada part
pendek-lebar. `support_volume_factor` diatur per teknologi, dan **MJF bernilai 0** karena part
tertopang serbuk di sekelilingnya — pada teknologi itu checkbox dinonaktifkan beserta
penjelasannya, bukan diam-diam menghasilkan 0 gram.

Struktur kodenya sudah disiapkan untuk pengembangan lanjutan:
`App\Services\SupportEstimator` (server) dan `resources/js/modules/support-estimator.js` (browser)
adalah satu-satunya tempat rumus support berada, sehingga deteksi otomatis kebutuhan support,
analisis sudut overhang dari mesh, dan perhitungan volume yang lebih akurat cukup menggantikan isi
`estimate()` tanpa menyentuh rumus biaya dan waktu. Jenis support (Normal / Tree) sudah terdaftar
di config beserta pengalinya.

### Beberapa model dalam satu penawaran

Area **Drag & Drop** dan tombol **Upload File** menerima banyak berkas sekaligus. Bila salah satu
berkas tidak valid, **hanya berkas itu yang dilaporkan** beserta namanya — berkas lain yang valid
tetap dimuat dan dapat langsung ditinjau.

- **Daftar Model** — seluruh model tampil sebagai kartu berisi nama file, format, ukuran, berat,
  estimasi biaya, dan status analisisnya. Klik salah satunya untuk meninjaunya lebih detail.
- **Satu viewer, model aktif bergantian.** Model yang dipilih ditampilkan di viewer 3D dengan
  seluruh kontrol yang sama (rotate, zoom, pan, reset view, preset sudut pandang, orientasi).
  Model lain tetap tersimpan lengkap di memori sehingga berpindah tidak perlu membaca ulang berkas.
- **Informasi, analisis, dan pengaturan berdiri sendiri.** Resolusi, support, teknologi, material,
  jumlah cetak, dan orientasi tersimpan per model — mengubah salah satunya tidak menyentuh model
  lain. Setiap panel pengaturan menampilkan penanda model yang sedang diubah.
- **Ringkasan Penawaran** di bawah halaman menampilkan tabel seluruh model (nama file, teknologi &
  material, jumlah, berat, waktu, biaya) beserta **total jumlah model, berat, waktu, dan biaya**.

Jumlah maksimal model per permintaan diatur lewat `limits.max_models_per_quotation` di
`config/printing.php` (default 25), dan dibatasi lagi oleh `max_file_uploads` pada `php.ini`.

### Request Quotation

Seluruh isi halaman 3D Models — unggah file, pratinjau 3D, dan semua simulasi (support, layer
height, infill, material, estimasi) — tetap terbuka untuk pengunjung **tanpa akun**. Yang menuntut
login hanya pengiriman penawarannya.

Bila tombol **Minta Penawaran** ditekan tanpa akun, muncul popup *"Silakan login atau membuat akun
terlebih dahulu untuk membuat penawaran."* beserta tombol **Login** dan **Register**. Pemeriksaannya
berlapis: di browser lewat `printingConfig.auth.check`, dan di server lewat middleware `auth` pada
route `quotations.store` (permintaan JSON tanpa sesi dijawab 401, lalu form memunculkan popup yang
sama).

Setelah masuk, tombol yang sama membuka form (nama, email, WhatsApp, perusahaan, catatan) yang
**terisi otomatis dari profil akun** — jumlah cetak tidak diisi di sini karena sudah menjadi
pengaturan per model. Modal menampilkan daftar seluruh model yang akan ikut terkirim beserta
totalnya.

Saat dikirim, **seluruh berkas model baru diunggah ke server dalam satu permintaan** bersama hasil
analisis, pilihan teknologi/material, dan statistik tiap model. Sistem membuat **satu Nomor
Tracking** untuk keseluruhannya: baris penawaran disimpan di `quotation_requests`, sedangkan tiap
model disimpan sebagai baris `quotation_items` yang terkait dengannya.

Server **menghitung ulang estimasi tiap model** memakai `App\Services\PrintEstimator`, lalu
menjumlahkannya ke baris penawaran. Bila salah satu model ditolak validasi, seluruh permintaan
dibatalkan — permintaan yang sudah terkirim tidak boleh kehilangan model diam-diam.

**Privasi:** selama tahap pratinjau dan analisis, file tidak pernah meninggalkan perangkat pengguna —
seluruhnya dibaca lewat `FileReader`. Unggahan hanya terjadi bila pengguna menekan tombol penawaran.
File disimpan pada disk privat (`storage/app/private/quotations`) yang tidak dapat diakses lewat URL.

> **Batas ukuran:** satu penawaran memuat maksimal **25 file** berukuran **300 MB per file**
> (`limits.max_models_per_quotation` dan `limits.max_file_size_mb` di `config/printing.php`),
> dengan format tetap `.STL` dan `.OBJ`. Batas itu dipotong lagi oleh `upload_max_filesize`,
> `post_max_size`, dan `max_file_uploads` pada `php.ini` — `App\Support\UploadLimit` selalu memakai
> nilai terkecil di antara keduanya (`maxBytes()` per file, `maxTotalBytes()` untuk gabungan,
> `maxFiles()` untuk jumlahnya). Form menolak unggahan kelewat besar dengan pesan yang menyebutkan
> angka batasnya. Lihat [Batas unggah pada php.ini](#batas-unggah-pada-phpini) untuk setelan yang
> diperlukan agar angka 25 × 300 MB benar-benar berlaku.

---

## Bukti Penawaran (PDF) & Tracking

Setelah permintaan penawaran terkirim, modal sukses menampilkan **Nomor Penawaran / Tracking**
beserta dua tombol: **Download Bukti Penawaran (PDF)** dan **Buka Halaman Tracking**.

### Nomor tracking

Berformat `QTN-YYYYMMDD-XXXXXX`, misalnya `QTN-20260730-UBCDBP`.

Enam karakter terakhir **acak, bukan nomor urut**. Halaman tracking dapat diakses tanpa login,
jadi nomor berurutan (`-0001`, `-0002`, …) akan membuat data permintaan pelanggan lain mudah
ditebak satu per satu. Sebagai lapisan tambahan, halaman tracking **menyamarkan email dan nomor
WhatsApp** (`bu***@contoh.test`, `********7890`) dan diberi `noindex, nofollow`; data lengkapnya
hanya ada di PDF yang diunduh pelanggan sendiri.

### PDF

Dibuat dengan **dompdf**, QR code dengan **bacon/bacon-qr-code**. Keduanya dipilih karena PHP di
lingkungan ini tidak memuat ekstensi GD maupun Imagick — logo dan QR disematkan sebagai data URI
**SVG**, bukan PNG. Font subsetting diaktifkan di `config/dompdf.php`; tanpa itu font DejaVu utuh
membuat berkas melebihi 800 KB (dengan subsetting: ±34 KB).

Isi PDF: header berlogo dengan warna `#95271D`, data pelanggan, **tabel daftar seluruh model**
(nama file, teknologi & material, resolusi, jumlah, berat, estimasi, beserta baris total),
informasi penawaran, QR code menuju halaman tracking, dan catatan penyimpanan dokumen.

### Alur status

Seluruh status didefinisikan di `quotation_statuses` pada `config/printing.php` dan dibaca lewat
`App\Support\QuotationStatus`. Statusnya terbagi dua grup.

**Grup `flow`** — sembilan tahap yang berjalan maju dan membentuk timeline:

| Kunci | Label |
| --- | --- |
| `received` | Menunggu Review |
| `reviewing` | File Sedang Direview |
| `awaiting_approval` | Menunggu Persetujuan Penawaran |
| `awaiting_payment` | Menunggu Pembayaran |
| `payment_received` | Pembayaran Diterima |
| `production` | Sedang Diproduksi |
| `quality_control` | Quality Control |
| `ready_to_ship` | Siap Dikirim |
| `completed` | Selesai |

**Grup `cancellation`** — keadaan pembatalan di luar timeline, dipasang oleh aksi pembatalan
(bukan dropdown status admin):

| Kunci | Label |
| --- | --- |
| `cancelled_by_user` | Dibatalkan oleh User |
| `cancellation_requested` | Permintaan Pembatalan |
| `cancellation_approved` | Pembatalan Disetujui |
| `cancellation_rejected` | Pembatalan Ditolak |

Tahap `received` ditandai `editable` di config — selama status masih di situ, pemiliknya boleh
menyunting isi penawaran (lihat [Dashboard User](#dashboard-user)).

Timeline mewarnai tahap **sebelum** status sekarang hijau, tahap sekarang dengan warna brand, dan
sisanya abu-abu. Saat pembatalan sedang diajukan, timeline tetap memperlihatkan tahap terakhir yang
benar-benar dijalani (`status_before_cancellation`). Menambah atau menyusun ulang tahap cukup
dilakukan di config — timeline, filter admin, notifikasi, dan riwayat mengikutinya otomatis.

> Alur sebelumnya (`analyzed`, `quoted`, `payment`, `shipped`) dipetakan otomatis ke alur baru oleh
> migrasi `2026_08_01_000015_map_quotation_statuses_to_new_flow`, sehingga penawaran dan riwayat
> lama tetap terbaca pada timeline.

### Riwayat

Setiap perubahan status atau catatan admin **menambah** baris di tabel `quotation_histories` —
tidak pernah menimpa yang lama, sehingga jejak perkembangan tetap utuh dan tampil sebagai tabel
di halaman tracking maupun dashboard admin.

> **Catatan struktur database.** Spesifikasi meminta tabel `quotations`, namun padanannya sudah ada
> sejak fitur Request Quotation dibuat, yaitu `quotation_requests` (menyimpan nama, email, WhatsApp,
> info file, dan status). Membuat tabel paralel akan menduplikasi data dan memecah fitur yang sudah
> berjalan, jadi tabel itu diperluas: kolom `reference` berganti nama menjadi `tracking_number`
> (satu nomor untuk pelanggan, PDF, QR, dan admin), ditambah `estimated_price`, `estimated_finish`,
> `production_photo`, dan `result_photo`. Tabel `quotation_histories` dibuat baru sesuai permintaan.
>
> Saat satu penawaran dapat berisi banyak model, tabel **`quotation_items`** dibuat sesuai relasi
> yang diminta (`quotations` → `quotation_items`), dengan `quotation_requests` tetap berperan
> sebagai tabel penawaran. Setiap item memegang berkas, pengaturan printing, hasil analisis, dan
> estimasinya sendiri. Kolom model pada `quotation_requests` **tidak dihapus**: kolom berkas dan
> pilihan produksi tetap diisi dari model pertama sebagai ringkasan, sedangkan kolom estimasi
> (`estimated_cost`, `estimated_minutes`, `quantity`, …) diisi penjumlahan seluruh model dan
> `analysis_status` diisi status terburuk di antaranya. Dengan begitu daftar admin, pencarian,
> halaman tracking, dan PDF tetap dapat membaca nilai yang mewakili tanpa memuat seluruh itemnya,
> dan permintaan lama otomatis dipindahkan menjadi satu item saat migrasi dijalankan.
>
> Pilihan simulasi ala Cura menyusul di migrasi berikutnya: `scale_percent`, `infill_density`,
> `infill_pattern`, `hollow_*`, `material_color`, `fits_build_volume`, dan `cost_breakdown` pada
> `quotation_items` karena semuanya diatur per model, ditambah `cost_breakdown` pada
> `quotation_requests` sebagai penjumlahannya.
>
> Sejak satu model dicetak pada satu mesin, `printer`, `printer_name`, dan `build_volume` juga
> berada di `quotation_items`. Kolom yang sama tetap ada di `quotation_requests` sebagai ringkasan
> berisi mesin model pertama, sehingga daftar admin dan dokumen lama tidak perlu memuat seluruh
> itemnya; `printer_summary` menyebut "N printer berbeda" bila mesinnya memang beragam.

## Akun: Login, Register & Password

Navbar website menampilkan **Login** dan **Register** di pojok kanan atas; setelah masuk keduanya
berganti menjadi **Dashboard** dan **Logout** (admin diarahkan ke dashboard admin, pelanggan ke
dashboard akunnya).

| Halaman | Route | Isi |
| --- | --- | --- |
| Login | `/login` | Email, Password, **Remember Me**, tautan **Lupa Password** |
| Register | `/register` | Nama Lengkap, Nomor Telepon, Kota Asal, Kode Pos, Alamat Lengkap, Email, Password, Konfirmasi Password |
| Lupa Password | `/lupa-password` | Email → tautan reset dikirim ke inbox |
| Buat Password Baru | `/reset-password/{token}` | Password baru + konfirmasinya |
| Ganti Password | `/dashboard/ganti-password` & `/admin/ganti-password` | Password Lama, Password Baru, Konfirmasi Password Baru |

Seluruh data pendaftaran divalidasi sebelum disimpan (`App\Http\Requests\Auth\RegisterRequest`,
memakai aturan bersama `ValidatesProfileFields`): nomor telepon hanya angka dan tanda umum, kode pos
tepat 5 angka, email unik, dan password minimal 8 karakter yang harus sama dengan konfirmasinya.
Aturan yang sama dipakai ulang oleh formulir Profil di dashboard.

**Lupa Password** memakai broker bawaan Laravel (tabel `password_reset_tokens`, token berlaku 60
menit dan hangus setelah dipakai). Pesan hasilnya sengaja selalu sama apa pun keadaan emailnya
sehingga halaman itu tidak dapat dipakai memeriksa email mana yang terdaftar. Pastikan konfigurasi
`MAIL_*` pada `.env` sudah benar; dengan `MAIL_MAILER=log` (default pengembangan) tautannya dapat
dibaca di `storage/logs/laravel.log`.

**Role.** Kolom `users.role` berisi `admin` atau `user`. Middleware `admin` menjaga seluruh area
`/admin` (pelanggan yang membukanya dikembalikan ke dashboardnya), sedangkan middleware `customer`
menjaga `/dashboard` (admin diarahkan ke dashboard admin). Halaman masuk admin menolak kredensial
akun pelanggan meskipun kata sandinya benar.

---

## Dashboard User

Akses di **`/dashboard`** setelah pelanggan masuk. Sidebarnya: **Dashboard**, **Penawaran Saya**,
**Notifikasi**, **Profil**, **Ganti Password**.

### Penawaran Saya

`/dashboard/penawaran` menampilkan seluruh penawaran milik akun tersebut: **Nomor Penawaran,
Tanggal, Jumlah File, Total Berat, Total Estimasi Biaya, Status, Tracking**, dan tombol **Detail
Penawaran**. Penawaran milik akun lain menghasilkan 404, bukan sekadar disembunyikan.

Halaman detail menampilkan ringkasan, daftar seluruh file beserta pengaturan printing-nya, timeline
status, dan riwayat perubahan.

### Edit penawaran

Selama status masih **Menunggu Review**, pemiliknya dapat membuka `/dashboard/penawaran/{id}/ubah`
untuk:

- **menambah file 3D** (`.STL`/`.OBJ`, mengikuti batas 25 file × 300 MB),
- **menghapus file 3D** (penawaran harus menyisakan minimal satu file),
- **mengubah pengaturan printing** tiap model: teknologi, material, printer, resolusi, skala,
  infill, warna, support, dan Hollow Model (khusus SLA),
- **mengubah jumlah cetak** tiap model.

Setiap perubahan **menghitung ulang estimasi di server** memakai `App\Services\PrintEstimator`, lalu
`QuotationRequest::refreshSummary()` menyusun ulang kolom ringkasan penawaran dari seluruh modelnya —
angka totalnya tidak pernah disalin dari browser.

Model yang ditambahkan dari dashboard tidak melewati viewer Three.js, jadi geometrinya diukur di
server oleh **`App\Services\MeshInspector`** (STL biner & ASCII, serta OBJ) dengan rumus yang sama:
volume dari jumlah tetrahedron bertanda, luas permukaan dari hasil kali silang tiap segitiga.
Berkasnya dibaca mengalir sehingga model besar tidak perlu dimuat seluruhnya ke memori. Model
seperti ini ditandai **perlu ditinjau engineer** karena analisis kelayakan lengkapnya memang hanya
berjalan di halaman 3D Models.

Begitu admin memindahkan status ke **File Sedang Direview**, seluruh data menjadi **read only**:
halaman ubah mengalihkan kembali ke detail, dan endpoint penyuntingannya menolak permintaan dengan
pesan yang menjelaskan alasannya.

### Pembatalan

| Status saat diajukan | Yang terjadi |
| --- | --- |
| Menunggu Review | Pembatalan **langsung berlaku** → status menjadi **Dibatalkan oleh User** |
| Sudah direview atau lebih lanjut | Status menjadi **Permintaan Pembatalan** dan menunggu keputusan admin |

Alasan pembatalan bersifat opsional dan ikut tersimpan, tampil pada dashboard admin agar dapat
ditindaklanjuti. Penawaran yang sudah **Selesai** atau sudah dibatalkan tidak dapat diajukan lagi.

---

## Notifikasi

Notifikasi memakai channel `database` bawaan Laravel (tabel `notifications`), dipakai dua arah:

| Pemicu | Penerima | Isi |
| --- | --- | --- |
| Penawaran baru dikirim | Seluruh admin | "Penawaran baru dari Andi Saputra." |
| Pengajuan pembatalan | Seluruh admin | "Permintaan pembatalan dari Budi Santoso." |
| Perubahan status oleh admin | Pemilik penawaran | Label & penjelasan status, mis. "File Sedang Direview" |
| Keputusan pembatalan | Pemilik penawaran | "Permintaan pembatalan diterima/ditolak" |

Kedua dashboard memiliki **ikon lonceng** pada header beserta jumlah notifikasi yang belum dibaca
(mis. 🔔 2), panel ringkas berisi delapan notifikasi terbaru, dan halaman **Notifikasi** lengkap
dengan penomoran halaman serta tombol *Tandai semua terbaca*.

`resources/js/dashboard.js` menarik endpoint `…/notifikasi/terbaru` setiap 20 detik, memperbarui
angka pada lonceng, dan memunculkan **popup** untuk notifikasi yang baru datang. Cara ini dipilih
agar pemberitahuan terasa langsung tanpa menuntut server WebSocket; bila kelak broadcasting
diaktifkan, tampilan lonceng dan popupnya tidak perlu diubah — hanya sumber datanya.

---

## Dashboard Admin

Akses di **`/admin/login`**. Sidebarnya: **Dashboard**, **Penawaran**, **User**, **Notifikasi**,
**Profil**, **Ganti Password**. Akun awal dibuat oleh `AdminUserSeeder` dan dapat diatur lewat
`.env`:

```
ADMIN_EMAIL=admin@nusama3d.com
ADMIN_PASSWORD=password
```

> Ganti `ADMIN_PASSWORD` sebelum website dipakai online, lalu jalankan ulang
> `php artisan db:seed --class=AdminUserSeeder`.

### Dashboard statistik

Halaman `/admin` menampilkan **Total Penawaran**, **Total Pesanan Selesai**, **Total Object 3D yang
Berhasil Dicetak** (jumlah unit dari penawaran berstatus Selesai), **Total Pendapatan**, dan
**Total User Terdaftar**, ditambah:

- **grafik statistik bulanan** 12 bulan terakhir (penawaran masuk, pesanan selesai, dan pendapatan),
  digambar sebagai batang CSS tanpa pustaka tambahan dan disertai tabel angkanya agar tetap terbaca
  saat dicetak maupun oleh pembaca layar;
- **sebaran status** seluruh penawaran;
- daftar **permintaan pembatalan** yang menunggu persetujuan;
- delapan penawaran terbaru.

Pendapatan dihitung dari penawaran berstatus **Selesai** memakai harga penawaran yang ditetapkan
admin (`estimated_price`), dan jatuh ke estimasi sistem bila harga resmi belum ditetapkan — angka
yang sama dengan yang dilihat pelanggan.

### Manajemen user

`/admin/pengguna` menampilkan seluruh akun pelanggan beserta kontak, kota, dan jumlah penawarannya,
dengan pencarian pada nama/email/telepon/kota. Halaman detail memuat profil lengkap, ringkasan
(total penawaran, pesanan selesai, nilai estimasi), **riwayat penawaran** akun tersebut, dan tombol
WhatsApp. Halaman ini hanya membaca — perubahan data akun tetap dilakukan pemiliknya lewat Profil.

### Persetujuan pembatalan

Bila pelanggan mengajukan pembatalan setelah berkasnya direview, halaman detail penawaran
menampilkan panel persetujuan berisi alasan pelanggan beserta dua tombol:

- **Setujui Pembatalan** → status menjadi **Pembatalan Disetujui** dan penawaran berhenti;
- **Tolak Pembatalan** → penawaran **kembali ke tahap yang sedang dijalaninya** sebelum pengajuan,
  dan penolakannya tercatat di riwayat sebagai **Pembatalan Ditolak**.

Keduanya dapat disertai catatan yang ikut terkirim sebagai notifikasi ke pelanggan.

### Tombol WhatsApp

Halaman detail penawaran menyediakan tombol **Hubungi Pelanggan via WhatsApp** yang langsung membuka
`wa.me` menuju nomor telepon akun pelanggan (awalan `0` diganti `62`). Dipakai untuk mengonfirmasi
alasan pembatalan, revisi desain, atau komunikasi lainnya.

### Manajemen penawaran

- Melihat seluruh permintaan penawaran: nama pelanggan, email, WhatsApp, nama file, teknologi,
  material, status analisis, estimasi biaya, dan tanggal upload
- Mencari berdasarkan nama/email/perusahaan/nama file/nomor referensi, serta memfilter
  berdasarkan status dan teknologi
- Membuka detail permintaan lengkap dengan informasi model dan seluruh butir analisis
- Mengunduh file STL/OBJ asli
- **Mengubah status tracking** (sembilan tahap alur) — tercatat otomatis ke riwayat dan langsung
  dikirim sebagai notifikasi ke dashboard pemiliknya
- **Mengubah estimasi harga** (menggantikan estimasi sistem) dan **estimasi penyelesaian**
- **Menambahkan catatan untuk pelanggan** — langsung tampil di halaman tracking
- **Mengunggah foto proses produksi dan foto hasil akhir** (opsional)
- Menambahkan catatan internal yang tidak tampil ke pelanggan
- Membuka halaman tracking pelanggan langsung dari halaman detail
- Menghapus permintaan (berkas model, foto, dan riwayatnya ikut terhapus otomatis)

## Foto pada Halaman Home

Foto di `public/images/photos/` adalah **foto stok berlisensi bebas pakai komersial**
(Unsplash License & Pexels License, tanpa kewajiban atribusi) — **bukan foto workshop Anda**.
Foto ini dipakai sebagai penahan sementara agar tata letaknya sudah final.

| File | Dipakai di | Sumber |
| --- | --- | --- |
| `machine-01.jpg` | Section Tentang (foto utama) | Unsplash |
| `workshop-01.jpg` | Section Tentang | Pexels |
| `detail-01.jpg` | Section Tentang | Unsplash |
| `workshop-02.jpg` | Pita foto "Di dalam workshop kami" | Pexels |
| `detail-02.jpg` | Pita foto "Di dalam workshop kami" | Pexels |

**Ganti dengan foto asli sesegera mungkin** — untuk company profile manufaktur, foto fasilitas
sendiri adalah pembeda kredibilitas terbesar. Cukup timpa file dengan nama yang sama
(rasio 4:3, sisi panjang ± 900–1200 px), tidak perlu mengubah kode.

Prioritas foto yang paling berdampak: **hasil cetak close-up** (tekstur layer & finishing) →
**mesin sedang bekerja** → **tim & workshop**.

## Mengubah Estimasi Harga & Batas Analisis

Seluruh parameter ada di **`config/printing.php`** — harga per gram, densitas material, tarif mesin,
kecepatan produksi, biaya setup, build volume, dan batas ukuran model. Rumusnya:

```
volume_model    = volume_geometri x skala^3
fill_factor     = shell_ratio + (1 - shell_ratio) x infill x pengali_pola
volume_material = volume_model x fill_factor          (atau luas_permukaan x tebal_dinding bila hollow)
berat           = volume_material (cm3) x densitas (g/cm3)
waktu           = setup_hours + volume_material / (throughput x kecepatan_printer)
biaya           = material + waktu mesin + support + finishing + quality control
```

`shell_ratio` tiap teknologi dikalibrasi agar pada infill bawaannya hasilnya sama persis dengan
fill factor yang dipakai sebelum infill dapat diatur — FDM 0,3125 pada infill 20% menghasilkan
0,45. SLA bernilai 1,0 karena resin mengeras padat; pengurangan materialnya lewat Hollow Model.

Rumus yang sama diterapkan di dua tempat agar angkanya konsisten:
`App\Services\PrintEstimator` (server) dan `resources/js/modules/print-estimator.js` (browser).
Angka yang tersimpan di database selalu **dihitung ulang di server**, tidak diambil dari kiriman klien.

## Logo Klien & Testimoni

Keduanya tersimpan di database dan tampil di halaman Home:

| Bagian | Posisi di Home | Tabel | Seeder |
| --- | --- | --- | --- |
| Logo klien | tepat di bawah hero | `clients` | `ClientSeeder` |
| Testimoni | tepat sebelum CTA penutup | `testimonials` | `TestimonialSeeder` |

**Logo klien** dipotong satu per satu dari materi "trusted by" perusahaan dengan mendeteksi batas
tiap logo dari pikselnya, lalu ditampilkan grayscale dan berwarna saat disentuh agar deretannya
menyatu tanpa mengalahkan warna brand. Menambah klien baru: taruh berkas di
`public/images/clients/`, lalu tambahkan satu entri di `ClientSeeder`.

**Testimoni** dikutip **apa adanya** dari Google Review — ejaan dan gaya bahasa penulisnya tidak
dirapikan. Beberapa catatan yang disengaja:

- **Foto profil pengulas tidak disalin** ke website. Avatarnya dibentuk dari inisial nama, sehingga
  tidak ada foto pribadi orang lain yang ikut dipublikasikan ulang.
- **Tanggal pasti tidak dikarang.** Kolom `reviewed_label` menyimpan keterangan waktu persis seperti
  yang tampil di sumbernya ("2 tahun lalu"), karena tanggal sebenarnya tidak diketahui.
- **Rata-rata dihitung dari ulasan yang ditampilkan saja** (`5,0 dari 10 ulasan yang ditampilkan`),
  bukan mengklaim total ulasan yang tidak dapat diverifikasi.
- **Tidak ada markup `aggregateRating`** pada structured data. Rich result rating menuntut angka
  yang mencerminkan seluruh ulasan; memasangnya dari 10 ulasan hasil tangkapan layar berisiko
  dianggap menyesatkan oleh Google. Bila nanti tersedia data lengkap dari Google Business Profile,
  markup ini dapat ditambahkan.

## Mengubah Konten

Seluruh konten tersimpan di database dan dapat diubah lewat seeder atau langsung di tabelnya:

- **Layanan** → tabel `services` / `database/seeders/ServiceSeeder.php`
- **Teknologi** → tabel `technologies` / `database/seeders/TechnologySeeder.php`
- **Profil, visi, misi, keunggulan, kontak, media sosial** → tabel `company_profiles` /
  `database/seeders/CompanyProfileSeeder.php`

Setelah mengubah seeder, jalankan ulang (seeder memakai `updateOrCreate`, jadi aman diulang):

```bash
php artisan db:seed
```

Untuk mengubah warna identitas, sunting token pada blok `@theme` di `resources/css/app.css`
(`--color-brand-*`), lalu build ulang asset.

---

## Pengujian

```bash
php artisan test
```

Pengujian memakai SQLite in-memory sehingga database MySQL pengembangan tidak tersentuh.
Cakupannya: seluruh route halaman publik & sitemap, penyimpanan permintaan penawaran beserta
validasinya, perhitungan ulang estimasi di server, halaman tracking & PDF, serta seluruh aksi
dashboard admin.

Berkas pengujian:

| Berkas | Cakupan |
| --- | --- |
| `PagesTest` | Route halaman publik & sitemap |
| `QuotationRequestTest` | Pengiriman penawaran, validasi, estimasi server, kepemilikan akun, notifikasi admin |
| `QuotationTrackingTest` | Halaman tracking, PDF, riwayat status |
| `AuthenticationTest` | Register, login, Remember Me, lupa & ganti password, pemisahan role |
| `UserDashboardTest` | Penawaran Saya, edit isi penawaran, `MeshInspector`, read only, pembatalan, notifikasi |
| `AdminManagementTest` | Statistik, manajemen user, persetujuan pembatalan, notifikasi, tombol WhatsApp |
| `AdminDashboardTest` | Daftar & detail penawaran, filter, unduh berkas, estimasi per model |

---

## Fitur Lain

- **Hero carousel full width** pada halaman utama: empat slide (FDM Printing, Resin SLA, Finishing,
  Workshop) membentang dari sisi kiri ke sisi kanan layar tanpa container, tinggi 540 px di ponsel
  sampai 750 px di desktop, gambar memakai `object-fit: cover`, ditumpuk overlay gelap 50% agar teks
  terbaca, sedangkan judul, deskripsi, dan tombol tetap berada di dalam container. Slide berganti
  otomatis tiap 6,5 detik dengan transisi halus, lengkap dengan indikator dan tombol maju/mundur.
  Videonya dimuat malas — hanya slide yang tampil yang diputar — otomatis berhenti saat kursor
  berada di atasnya, saat tab tidak terlihat, atau saat pengguna mengaktifkan *reduce motion*.
  Letakkan berkas videonya di `public/videos/` (lihat `public/videos/README.md`); selama belum ada,
  setiap slide menampilkan gambar posternya sehingga hero tetap utuh
- Responsif penuh untuk desktop, tablet, dan mobile
- **Top bar** putih berisi slogan "3D Printing Service & Engineering Solutions" beserta nomor
  WhatsApp, berada tepat di atas navbar
- **Navbar full width** (tanpa container): logo dan menu di kiri — Home, Services, Technologies,
  About, dan **Support Us** — sedangkan **Order Now** (outline, menuju halaman 3D Models)
  dan **Sign In** (primary) berada di kanan. Setelah masuk, Sign In berganti menjadi Dashboard dan
  Logout; pendaftaran akun tidak lagi dipajang di navbar dan hanya ditawarkan saat pengunjung
  hendak membuat penawaran. Halaman 3D Models tidak lagi menjadi butir menu tersendiri karena
  tombol Order Now sudah menuju ke sana
- **Support Us** membuka panel berisi kontak perusahaan — WhatsApp, Email, Telepon, Alamat Kantor,
  dan Jam Operasional — seluruhnya dibaca dari profil perusahaan di basis data
- Navbar sticky yang transparan di atas hero dan memadat saat digulir
- Smooth scrolling dengan `scroll-padding` agar anchor tidak tertutup navbar
- Animasi masuk ringan (AOS) yang otomatis nonaktif bila pengguna mengaktifkan
  *reduce motion* pada sistem operasinya
- SEO: title & meta description per halaman, canonical URL, Open Graph, Twitter Card,
  JSON-LD `LocalBusiness`, `robots.txt`, dan `sitemap.xml`
- Aksesibilitas: skip link, `aria-current` pada menu aktif, label pada kontrol ikon,
  dan fokus keyboard yang terlihat
- Keamanan akun: percobaan masuk dibatasi (5 kali per email + IP), kata sandi di-hash,
  sesi diregenerasi setelah masuk, dan penawaran milik akun lain menghasilkan 404
- Sidebar dashboard dapat dibuka-tutup pada layar kecil, dengan popup notifikasi yang
  otomatis menutup sendiri
