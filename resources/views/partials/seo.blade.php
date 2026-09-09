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

    $ogImage = asset('images/og-cover.svg');

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

{{-- Twitter --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $pageTitle }}">
<meta name="twitter:description" content="{{ $metaDescription }}">
<meta name="twitter:image" content="{{ $ogImage }}">

@php
    $structuredData = [
        '@context' => 'https://schema.org',
        '@type' => 'LocalBusiness',
        'name' => $company->name,
        'legalName' => $company->legal_name,
        'description' => $metaDescription,
        'url' => url('/'),
        'image' => $ogImage,
        'email' => $company->email,
        'telephone' => $company->phone,
        'foundingDate' => $company->founded_year,
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => $company->address,
            'addressLocality' => $company->city,
            'addressCountry' => 'ID',
        ],
        'sameAs' => array_values($company->socials ?? []),
    ];
@endphp

{{-- Structured data agar mesin pencari mengenali entitas perusahaan --}}
<script type="application/ld+json">
    {!! json_encode(array_filter($structuredData), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
