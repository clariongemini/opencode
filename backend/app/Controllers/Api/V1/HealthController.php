<?php

declare(strict_types=1);

namespace Kamelya\Controllers\Api\V1;

use Kamelya\Core\Config;
use Kamelya\Core\Database;
use Kamelya\Core\Request;
use Kamelya\Core\Response;
use PDOException;

/**
 * Sistem sağlık ucu — iş mantığı içermez, yalnızca DB erişilebilirliğini yoklar.
 * DB down ise 503 + standart hata dışı başarı gövdesi (durum izleme için 200 değil).
 */
final class HealthController
{
    public function saglik(Request $istek, array $rota = []): void
    {
        $veritabani = 'down';
        try {
            Database::baglanti()->query('SELECT 1');
            $veritabani = 'up';
        } catch (PDOException $hata) {
            error_log('[kamelya][health] db erisilemez: ' . $hata->getMessage());
        }

        $veri = [
            'status' => 'ok',
            'timestamp' => gmdate('c'),
            'db' => $veritabani,
            'version' => (string) Config::al('app.surum', '1.0.0'),
        ];

        if ($veritabani === 'down') {
            Response::basari($veri, null, 503);

            return;
        }

        Response::basari($veri);
    }
}
