<?php

declare(strict_types=1);

namespace Kamelya\Controllers\Api\V1;

use Kamelya\Core\Database;
use Kamelya\Core\Diller;
use Kamelya\Core\Hata;
use Kamelya\Core\Request;
use Kamelya\Core\Response;
use Kamelya\Repositories\UrunFiyatiRepository;
use Kamelya\Repositories\UrunRepository;
use Kamelya\Services\KarsilastirmaService;

/** GET /api/v1/karsilastir?ids=1,2&lang=tr — ince katman. */
final class KarsilastirmaController
{
    private KarsilastirmaService $service;

    public function __construct()
    {
        $pdo = Database::baglanti();
        $this->service = new KarsilastirmaService(new UrunRepository($pdo), new UrunFiyatiRepository($pdo));
    }

    /** @param array<string, string> $rota */
    public function karsilastir(Request $istek, array $rota = []): void
    {
        try {
            $dil = $istek->sorgu['lang'] ?? 'tr';
            if (!Diller::gecerli($dil)) {
                throw new Hata('VALIDATION_ERROR', 'lang parametresi geçersiz.', [['field' => 'lang', 'issue' => 'invalid']], 422);
            }

            $ham = (string) ($istek->sorgu['ids'] ?? '');
            $idListesi = [];
            foreach (explode(',', $ham) as $parca) {
                if (is_numeric(trim($parca)) && (int) trim($parca) > 0) {
                    $idListesi[] = (int) trim($parca);
                }
            }

            Response::basari($this->service->karsilastir($idListesi, $dil));
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }
}
