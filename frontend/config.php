<?php

declare(strict_types=1);

/** Frontend site ayarları — API tabanı, diller, iletişim. */
return [
    'api_taban' => getenv('KAMELYA_API') ?: 'http://127.0.0.1:8000/api/v1',
    'site_taban' => getenv('KAMELYA_SITE') ?: 'http://127.0.0.1:8080',
    'site_adi' => 'Kamelya',
    'diller' => ['tr', 'en', 'de', 'fr', 'it', 'ar'],
    'varsayilan_dil' => 'tr',
    'rtl_diller' => ['ar'],
    'whatsapp' => '905320000000',
    'telefon' => '+90 532 000 00 00',
    'eposta' => 'info@kamelya.local',
    'adres' => 'İstanbul, Türkiye',
    // Analitik (F6) — gerçek değerler sunucu ortamından; placeholder HTML'ye basılmaz.
    'ga4_id' => getenv('KAMELYA_GA4_ID') ?: 'G-XXXXXXXXXX',
    'gsc_dogrulama' => getenv('KAMELYA_GSC_DOGRULAMA') ?: 'XXXXXXXXXX',
    'clarity_id' => getenv('KAMELYA_CLARITY_ID') ?: 'XXXXXXXXXX',
];
