<?php

declare(strict_types=1);

use Kamelya\Core\Config;

return [
    'sunucu' => Config::cev('SMTP_HOST', '127.0.0.1'),
    'port' => (int) Config::cev('SMTP_PORT', '1025'),
    'kullanici' => Config::cev('SMTP_KULLANICI', ''),
    'sifre' => Config::cev('SMTP_SIFRE', ''),
    // yok|tls|ssl — yerelde yok, prodda tls önerilir.
    'sifreleme' => Config::cev('SMTP_SIFRELEME', 'yok'),
    'gonderen' => Config::cev('SMTP_GONDEREN', 'info@kamelya.local'),
];
