<?php

declare(strict_types=1);

use Kamelya\Core\Config;

return [
    'jwt_access_sure' => 3600,
    'jwt_refresh_sure' => 7 * 24 * 3600,
    // Dosya yükleme (F7 `urun_resimleri` akışında uygulanacak — kural şimdi kilitlenir).
    'yukleme' => [
        'izinli_mime' => ['image/jpeg', 'image/png', 'image/webp'],
        'maks_boyut' => 5 * 1024 * 1024,
        'dizin' => 'storage/yuklemeler',
        'rastgele_ad' => true,
        'public_disi' => true,
    ],
    'sifre' => ['min_uzunluk' => 12],
];
