<?php

declare(strict_types=1);

namespace Kamelya\Controllers\Api\V1;

use Kamelya\Core\Database;
use Kamelya\Core\Hata;
use Kamelya\Core\Request;
use Kamelya\Core\Response;
use Kamelya\Repositories\SssRepository;
use Kamelya\Services\SssService;

/** Public SSS uçları — GET /api/v1/sss-sorulari?lang=&kapsam= */
final class SssController
{
    private SssService $service;

    public function __construct()
    {
        $pdo = Database::baglanti();
        $this->service = new SssService(new SssRepository($pdo));
    }

    /** @param array<string, string> $rota */
    public function liste(Request $istek, array $rota = []): void
    {
        try {
            $sonuc = $this->service->liste(
                $istek->sorgu['lang'] ?? null,
                $istek->sorgu['kapsam'] ?? null
            );
            Response::basari($sonuc['satirlar'], $sonuc['meta']);
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }
}
