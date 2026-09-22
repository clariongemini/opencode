<?php

declare(strict_types=1);

namespace Kamelya\Controllers\Api\V1;

use Kamelya\Core\Database;
use Kamelya\Core\Hata;
use Kamelya\Core\Request;
use Kamelya\Core\Response;
use Kamelya\Repositories\Admin\AyarYonetimRepository;
use Kamelya\Repositories\BildirimRepository;
use Kamelya\Repositories\FiyatCarpaniRepository;
use Kamelya\Repositories\KategoriRepository;
use Kamelya\Repositories\TalepRepository;
use Kamelya\Repositories\UrunFiyatiRepository;
use Kamelya\Repositories\UrunRepository;
use Kamelya\Services\AuditLogService;
use Kamelya\Services\BildirimService;
use Kamelya\Services\HesapService;
use Kamelya\Services\TalepService;
use Kamelya\Validators\TalepValidator;

/** POST /api/v1/leads — F1.2 §3.4 (201 + Location). */
final class TalepController
{
    private TalepService $service;

    public function __construct()
    {
        $pdo = Database::baglanti();
        $this->service = new TalepService(
            $pdo,
            new TalepRepository($pdo),
            new UrunRepository($pdo),
            new HesapService(
                new UrunFiyatiRepository($pdo),
                new KategoriRepository($pdo),
                new FiyatCarpaniRepository($pdo)
            ),
            new BildirimService($pdo, new BildirimRepository($pdo), new AyarYonetimRepository($pdo)),
            new AuditLogService($pdo)
        );
    }

    /** @param array<string, string> $rota */
    public function olustur(Request $istek, array $rota = []): void
    {
        try {
            $veri = $istek->govde ?? [];
            $dogrulama = TalepValidator::dogrula($veri);
            if (!$dogrulama['gecerli']) {
                throw new Hata('VALIDATION_ERROR', 'Doğrulama hatası.', $dogrulama['hatalar'], 422);
            }

            $veri['ip_adresi'] = $istek->istemciIp;
            $sonuc = $this->service->olustur($veri);

            header('Location: /api/v1/leads/' . $sonuc['id']);
            Response::basari($sonuc, null, 201);
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }
}
