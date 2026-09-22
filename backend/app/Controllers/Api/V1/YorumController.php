<?php

declare(strict_types=1);

namespace Kamelya\Controllers\Api\V1;

use Kamelya\Core\Database;
use Kamelya\Core\Diller;
use Kamelya\Core\Hata;
use Kamelya\Core\Request;
use Kamelya\Core\Response;
use Kamelya\Repositories\Admin\AyarYonetimRepository;
use Kamelya\Repositories\BildirimRepository;
use Kamelya\Repositories\UrunRepository;
use Kamelya\Repositories\YorumRepository;
use Kamelya\Services\AuditLogService;
use Kamelya\Services\BildirimService;
use Kamelya\Services\YorumService;
use Kamelya\Validators\YorumValidator;

/** Public yorum uçları — yalnızca onaylılar okunur. */
final class YorumController
{
    private YorumService $service;

    public function __construct()
    {
        $pdo = Database::baglanti();
        $this->service = new YorumService(
            $pdo,
            new YorumRepository($pdo),
            new UrunRepository($pdo),
            new AuditLogService($pdo),
            new BildirimService($pdo, new BildirimRepository($pdo), new AyarYonetimRepository($pdo))
        );
    }

    /** @param array<string, string> $rota */
    public function olustur(Request $istek, array $rota = []): void
    {
        try {
            $veri = $istek->govde ?? [];
            $dogrulama = YorumValidator::dogrula($veri);
            if (!$dogrulama['gecerli']) {
                throw new Hata('VALIDATION_ERROR', 'Doğrulama hatası.', $dogrulama['hatalar'], 422);
            }

            Response::basari($this->service->gonder($veri, $istek->istemciIp), null, 201);
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }

    /** @param array<string, string> $rota */
    public function liste(Request $istek, array $rota = []): void
    {
        $dil = $istek->sorgu['lang'] ?? 'tr';
        if (!Diller::gecerli($dil)) {
            Response::hata('VALIDATION_ERROR', 'lang parametresi geçersiz.', [['field' => 'lang', 'issue' => 'invalid']], 422);

            return;
        }

        $urunId = isset($istek->sorgu['urun_id']) && $istek->sorgu['urun_id'] !== ''
            ? (int) $istek->sorgu['urun_id'] : null;
        $sayfa = max(1, (int) ($istek->sorgu['page'] ?? 1));
        $adet = min(50, max(1, (int) ($istek->sorgu['limit'] ?? 10)));
        $oneCikan = ($istek->sorgu['one_cikan'] ?? '') === '1';

        $sonuc = $this->service->liste($urunId, $dil, $sayfa, $adet, $oneCikan);
        Response::basari($sonuc['satirlar'], [
            'page' => $sayfa,
            'per_page' => $adet,
            'total' => $sonuc['toplam'],
            'ortalama_puan' => $sonuc['ortalama'],
        ]);
    }

    /** @param array<string, string> $rota */
    public function ozet(Request $istek, array $rota = []): void
    {
        $urunId = isset($istek->sorgu['urun_id']) && $istek->sorgu['urun_id'] !== ''
            ? (int) $istek->sorgu['urun_id'] : null;
        Response::basari($this->service->ozet($urunId));
    }
}
