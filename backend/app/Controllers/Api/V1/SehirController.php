<?php

declare(strict_types=1);

namespace Kamelya\Controllers\Api\V1;

use Kamelya\Core\Database;
use Kamelya\Core\Diller;
use Kamelya\Core\Hata;
use Kamelya\Core\Request;
use Kamelya\Core\Response;
use Kamelya\Repositories\SehirRepository;
use Kamelya\Services\SehirService;

/** Public şehir uçları. */
final class SehirController
{
    private SehirService $service;

    public function __construct()
    {
        $pdo = Database::baglanti();
        $this->service = new SehirService($pdo, new SehirRepository($pdo));
    }

    /** @param array<string, string> $rota */
    public function liste(Request $istek, array $rota = []): void
    {
        $dil = $istek->sorgu['lang'] ?? 'tr';
        if (!Diller::gecerli($dil)) {
            Response::hata('VALIDATION_ERROR', 'lang parametresi geçersiz.', [['field' => 'lang', 'issue' => 'invalid']], 422);

            return;
        }

        Response::basari($this->service->liste($dil));
    }

    /** @param array<string, string> $rota */
    public function detay(Request $istek, array $rota = []): void
    {
        try {
            $dil = $istek->sorgu['lang'] ?? 'tr';
            if (!Diller::gecerli($dil)) {
                throw new Hata('VALIDATION_ERROR', 'lang parametresi geçersiz.', [['field' => 'lang', 'issue' => 'invalid']], 422);
            }

            Response::basari($this->service->detay($dil, (string) ($rota['slug'] ?? '')));
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }
}
