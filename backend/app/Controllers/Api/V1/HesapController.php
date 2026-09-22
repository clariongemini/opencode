<?php

declare(strict_types=1);

namespace Kamelya\Controllers\Api\V1;

use Kamelya\Core\Database;
use Kamelya\Core\Hata;
use Kamelya\Core\Request;
use Kamelya\Core\Response;
use Kamelya\Repositories\FiyatCarpaniRepository;
use Kamelya\Repositories\KapasiteCarpaniRepository;
use Kamelya\Repositories\KategoriRepository;
use Kamelya\Repositories\UrunFiyatiRepository;
use Kamelya\Services\HesapService;
use Kamelya\Validators\HesapValidator;

/** POST /api/v1/calculate — F1.2 §3.3 sözleşmesi. */
final class HesapController
{
    private HesapService $service;

    public function __construct()
    {
        $pdo = Database::baglanti();
        $this->service = new HesapService(
            new UrunFiyatiRepository($pdo),
            new KategoriRepository($pdo),
            new FiyatCarpaniRepository($pdo),
            new KapasiteCarpaniRepository($pdo)
        );
    }

    /** @param array<string, string> $rota */
    public function hesapla(Request $istek, array $rota = []): void
    {
        try {
            $veri = $istek->govde ?? [];
            $dogrulama = HesapValidator::dogrula($veri);
            if (!$dogrulama['gecerli']) {
                throw new Hata('VALIDATION_ERROR', 'Doğrulama hatası.', $dogrulama['hatalar'], 422);
            }

            Response::basari($this->service->hesapla($veri));
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }
}
