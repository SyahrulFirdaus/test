<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    /*
    | Google tag (gtag.js) untuk Google Ads.
    |
    | Dikosongkan di .env untuk mematikan pemasangannya — mis. di lingkungan
    | pengembangan, supaya kunjungan localhost tidak ikut terhitung sebagai
    | lalu lintas iklan. Lihat resources/views/partials/google-tag.blade.php.
    */
    'google_ads' => [
        'id' => env('GOOGLE_ADS_ID', 'AW-17850382906'),
        // Label konversi "lead" Google Ads (mis. AW-17850382906/AbCdEf), opsional.
        // Bila diisi, halaman Terima Kasih juga mengirim event konversi ini.
        'lead_conversion' => env('GOOGLE_ADS_LEAD_CONVERSION'),
    ],

    /*
    | Google Tag Manager (opsional). Bila diisi, container GTM dimuat setelah
    | default Consent Mode v2; event situs (generate_lead, quote_submitted,
    | whatsapp_click, dst.) sudah dikirim ke dataLayer untuk dipakai trigger
    | "Custom Event" di GTM.
    */
    'google_tag_manager' => [
        'id' => env('GTM_ID'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
