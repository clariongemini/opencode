<?php

declare(strict_types=1);

namespace Kamelya\Controllers\Api\V1;

use Kamelya\Core\Database;
use Kamelya\Core\Hata;
use Kamelya\Core\Request;
use Kamelya\Core\Response;
use Kamelya\Repositories\Admin\AyarYonetimRepository;
use Kamelya\Repositories\BildirimRepository;
use Kamelya\Repositories\TalepRepository;
use Kamelya\Repositories\UrunRepository;
use Kamelya\Services\BildirimService;
use Kamelya\Services\HesapService;
use Kamelya\Services\IletisimService;
use Kamelya\Services\TalepService;
use Kamelya\Repositories\FiyatCarpaniRepository;
use Kamelya\Repositories\KategoriRepository;
use Kamelya\Repositories\UrunFiyatiRepository;
use Kamelya\Validators\IletisimValidator;

/** POST /api/v1/iletisim — herkese açık iletişim formu (tur=iletisim). */
final class IletisimController
{
    private IletisimService $service;

    public function __construct()
    {
        $pdo = Database::baglanti();
        $talepService = new TalepService(
            $pdo,
            new TalepRepository($pdo),
            new UrunRepository($pdo),
            new HesapService(
                new UrunFiyatiRepository($pdo),
                new KategoriRepository($pdo),
                new FiyatCarpaniRepository($pdo)
            ),
            new BildirimService($pdo, new BildirimRepository($pdo), new AyarYonetimRepository($pdo))
        );
        $this->service = new IletisimService(
            $talepService,
            new BildirimService($pdo, new BildirimRepository($pdo), new AyarYonetimRepository($pdo))
        );
    }

    /** @param array<string, string> $rota */
    public function gonder(Request $istek, array $rota = []): void
    {
        try {
            $veri = $istek->govde ?? [];
            $dogrulama = IletisimValidator::dogrula($veri);
            if (!$dogrulama['gecerli']) {
                throw new Hata('VALIDATION_ERROR', 'Doğrulama hatası.', $dogrulama['hatalar'], 422);
            }

            $veri['ip_adresi'] = $istek->istemciIp;
            Response::basari($this->service->gonder($veri, $istek->istemciIp), null, 201);
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }
}
