<?php

declare(strict_types=1);

namespace Kamelya\Middleware;

use Kamelya\Core\Config;
use Kamelya\Core\Request;
use Kamelya\Core\Response;

/**
 * Uç-bazlı hız limiti (IP + metot + yol + pencere kovası).
 * Aşımda 429 + Retry-After. Depo: sys temp (tek sunucu F5 kapsamı).
 */
final class RateLimitMiddleware
{
    /** @var array<string, array{0: int, 1: int}> uç → [limit, pencere_sn] */
    private const LIMITLER = [
        'POST /api/v1/calculate' => [30, 60],
        'POST /api/v1/leads' => [3, 3600],
        'POST /api/v1/appointments' => [5, 3600],
        'POST /api/v1/auth/login' => [5, 900],
        'POST /api/v1/yorumlar' => [3, 86400],
        'POST /api/v1/iletisim' => [3, 3600],
        'POST /api/v1/bulten' => [3, 3600],
        'GET /api/v1/arama' => [60, 60],
    ];

    private const VARSAYILAN = [120, 60];

    public function isle(Request $istek, callable $sonraki): void
    {
        [$limit, $pencere] = self::LIMITLER[$istek->metot . ' ' . $istek->yol] ?? self::VARSAYILAN;

        $dizin = sys_get_temp_dir() . '/kamelya_hiz';
        if (!is_dir($dizin)) {
            mkdir($dizin, 0700, true);
        }

        $dosya = $dizin . '/' . md5($istek->istemciIp . '|' . $istek->metot . '|' . $istek->yol . '|' . $pencere) . '.json';
        $simdi = time();
        $kayit = ['baslangic' => $simdi, 'sayac' => 0];
        if (is_file($dosya)) {
            $ham = json_decode((string) file_get_contents($dosya), true);
            if (is_array($ham) && ($simdi - (int) ($ham['baslangic'] ?? 0)) < $pencere) {
                $kayit = $ham;
            }
        }

        $kayit['sayac'] = (int) ($kayit['sayac'] ?? 0) + 1;
        file_put_contents($dosya, json_encode($kayit), LOCK_EX);

        $kalan = max(0, $limit - $kayit['sayac']);
        header('X-RateLimit-Limit: ' . $limit);
        header('X-RateLimit-Remaining: ' . $kalan);

        if ($kayit['sayac'] > $limit) {
            $bekleme = $pencere - ($simdi - (int) $kayit['baslangic']);
            header('Retry-After: ' . max(1, $bekleme));
            Response::hata('RATE_LIMITED', 'İstek limiti aşıldı, lütfen bekleyin.', [], 429);

            return;
        }

        $sonraki($istek);
    }
}
