<?php

declare(strict_types=1);

use Kamelya\Core\Config;

return [
    // Service Account JSON dosya yolu (sunucu ortamından; commit dışı).
    'servis_hesabi' => Config::cev('GSC_SERVICE_ACCOUNT_JSON', ''),
    'site_url' => Config::cev('GSC_SITE_URL', ''),
    // Yanıt önbelleği (saniye) — GSC ücretsiz kotası (2000 sorgu/gün) koruması.
    'onbellek_sure' => 3600,
];
