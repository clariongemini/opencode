<?php

declare(strict_types=1);

namespace Kamelya\Controllers\Api\V1;

use Kamelya\Core\Request;
use Kamelya\Core\Response;

/**
 * Public-dışı depodan güvenli dosya sunumu.
 * Yol allowlist'li (tip + desen); traversal realpath ile kapatılır.
 */
final class DosyaController
{
    /** @var array<string, string> */
    private const TIPLER = [
        'urunler' => 'urunler',
        'blog' => 'blog',
        'galeri' => 'galeri',
        'atolye' => 'atolye',
    ];

    /** @param array<string, string> $rota */
    public function sun(Request $istek, array $rota = []): void
    {
        $tip = $rota['tip'] ?? '';
        $ad = $rota['ad'] ?? '';

        if (!isset(self::TIPLER[$tip]) || preg_match('/^kml_[A-Za-z0-9.]+\.(jpg|png|webp)$/', $ad) !== 1) {
            Response::hata('NOT_FOUND', 'Dosya bulunamadı.', [], 404);

            return;
        }

        $kok = realpath(dirname(__DIR__, 4) . '/storage/yuklemeler/' . $tip);
        $tam = realpath(dirname(__DIR__, 4) . '/storage/yuklemeler/' . $tip . '/' . basename($ad));
        if ($kok === false || $tam === false || !str_starts_with($tam, $kok) || !is_file($tam)) {
            Response::hata('NOT_FOUND', 'Dosya bulunamadı.', [], 404);

            return;
        }

        $mime = match (strtolower(pathinfo($tam, PATHINFO_EXTENSION))) {
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            default => 'image/webp',
        };

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($tam));
        header('Cache-Control: public, max-age=86400');
        readfile($tam);
    }
}
