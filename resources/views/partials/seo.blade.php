@php
    // Konten @section inline sudah di-escape Laravel; dikembalikan dulu ke teks
    // mentah agar tidak ter-escape dua kali saat dicetak sebagai meta tag.
    $metaTitle = trim(html_entity_decode($__env->yieldContent('title'), ENT_QUOTES));
    $pageTitle = $metaTitle !== ''
        ? $metaTitle.' | '.$company->name
        : $company->name.' | '.$company->tagline;

    $metaDescription = trim(html_entity_decode($__env->yieldContent('description'), ENT_QUOTES));
    $metaDescription = $metaDescription !== ''
        ? $metaDescription
        : ($company->short_description ?: 'Layanan 3D printing, desain, dan reverse engineering untuk kebutuhan industri.');
    $metaDescription = \Illuminate\Support\Str::limit(strip_tags($metaDescription), 158);

    // Gambar pratinjau tautan (WhatsApp, Facebook, LinkedIn, X): JPG 1200×630
    // di public/images/og/{nama}.jpg. Halaman memilih gambarnya lewat
    // @section('og-image', 'services'); tanpa itu dipakai default.jpg.
    // SVG sengaja tidak dipakai karena tidak didukung platform tersebut.
    $ogKey = trim($__env->yieldContent('og-image')) ?: 'default';
    $ogPath = 'images/og/'.$ogKey.'.jpg';

    if (! is_file(public_path($ogPath))) {
        $ogPath = 'images/og/default.jpg';
    }

    // Versi berkas ikut di URL supaya cache pratinjau platform diperbarui
    // ketika gambarnya diganti.
    $ogImage = asset($ogPath).'?v='.filemtime(public_path($ogPath));
    $ogImageAlt = $pageTitle;

    // Halaman tertentu — misalnya tracking penawaran yang memuat data pelanggan —
    // menimpa nilai ini lewat @section('robots').
    $robots = trim($__env->yieldContent('robots')) ?: 'index, follow, max-image-preview:large';
@endphp

<title>{{ $pageTitle }}</title>
<meta name="description" content="{{ $metaDescription }}">
<meta name="keywords" content="3D printing, jasa cetak 3D, additive manufacturing, FDM, SLA, MJF, SLM, 3D scanning, reverse engineering, rapid prototyping, {{ $company->city }}">
<meta name="author" content="{{ $company->name }}">
<meta name="robots" content="{{ $robots }}">
<meta name="theme-color" content="#95271D">
<link rel="canonical" href="{{ url()->current() }}">

{{-- Open Graph --}}
<meta property="og:type" content="website">
<meta property="og:locale" content="id_ID">
<meta property="og:site_name" content="{{ $company->name }}">
<meta property="og:title" content="{{ $pageTitle }}">
<meta property="og:description" content="{{ $metaDescription }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:image" content="{{ $ogImage }}">
@if (str_starts_with($ogImage, 'https://'))
<meta property="og:image:secure_url" content="{{ $ogImage }}">
@endif
<meta property="og:image:type" content="image/jpeg">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="{{ $ogImageAlt }}">

{{-- Twitter --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $pageTitle }}">
<meta name="twitter:description" content="{{ $metaDescription }}">
<meta name="twitter:image" content="{{ $ogImage }}">
<meta name="twitter:image:alt" content="{{ $ogImageAlt }}">

@php
    // Schema LocalBusiness dari data Profil Perusahaan (App\Support\BusinessSchema):
    // nama, alamat, telepon, jam buka, dan tautan peta. Gambar memakai
    // pratinjau default agar konsisten di semua halaman.
    $structuredData = \App\Support\BusinessSchema::for(
        $company,
        $metaDescription,
        is_file(public_path('images/og/default.jpg')) ? asset('images/og/default.jpg') : null,
    );
@endphp

{{-- Structured data agar mesin pencari mengenali entitas perusahaan --}}
<script type="application/ld+json">
    {{-- JSON_HEX_TAG: teks berisi "</script>" tidak dapat menutup tag ini. --}}
    {!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}
</script>
