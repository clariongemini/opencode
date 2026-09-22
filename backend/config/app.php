<?php

declare(strict_types=1);

use Kamelya\Core\Config;

return [
    'ortam' => Config::cev('APP_ENV', 'local'),
    'hata_ayiklama' => filter_var(Config::cev('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN),
    'url' => rtrim((string) Config::cev('APP_URL', 'http://localhost:8000'), '/'),
    'surum' => '1.0.0',
    'cors_kokenler' => Config::cev('CORS_ORIGINS', '*'),
    'hiz_limiti' => (int) Config::cev('RATE_LIMIT', '120'),
];
