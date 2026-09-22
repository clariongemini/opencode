<?php

declare(strict_types=1);

namespace Kamelya\Controllers\Api\V1;

use Kamelya\Core\Database;
use Kamelya\Core\Diller;
use Kamelya\Core\Hata;
use Kamelya\Core\Request;
use Kamelya\Core\Response;
use Kamelya\Services\AramaService;

/** GET /api/v1/arama — public site araması (60/dk limit). */
final class AramaController
{
    private AramaService $service;

    public function __construct()
    {
        $this->service = new AramaService(Database::baglanti());
    }

    /** @param array<string, string> $rota */
    public function ara(Request $istek, array $rota = []): void
    {
        try {
            $q = trim((string) ($istek->sorgu['q'] ?? ''));
            if (mb_strlen($q) < 3) {
                throw new Hata('VALIDATION_ERROR', 'En az 3 karakter yazın.', [['field' => 'q', 'issue' => 'too_short']], 422);
            }

            $dil = $istek->sorgu['lang'] ?? 'tr';
            if (!Diller::gecerli($dil)) {
                throw new Hata('VALIDATION_ERROR', 'lang parametresi geçersiz.', [['field' => 'lang', 'issue' => 'invalid']], 422);
            }

            $tur = (string) ($istek->sorgu['tur'] ?? 'hepsi');
            if (!in_array($tur, AramaService::TURLER, true)) {
                throw new Hata('VALIDATION_ERROR', 'tur parametresi geçersiz.', [['field' => 'tur', 'issue' => 'invalid']], 422);
            }

            $sayfa = max(1, (int) ($istek->sorgu['sayfa'] ?? 1));
            $adet = min(50, max(1, (int) ($istek->sorgu['limit'] ?? 20)));

            $sonuc = $this->service->ara($q, $dil, $tur, $sayfa, $adet);

            Response::json([
                'success' => true,
                'data' => [
                    'sonuclar' => $sonuc['sonuclar'],
                    'toplam' => $sonuc['toplam'],
                    'sayfa' => $sayfa,
                    'limit' => $adet,
                ],
            ]);
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }
}
