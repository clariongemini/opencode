<?php

declare(strict_types=1);

namespace Kamelya\Controllers\Api\V1;

use Kamelya\Core\Database;
use Kamelya\Core\Hata;
use Kamelya\Core\Request;
use Kamelya\Core\Response;
use Kamelya\Repositories\Admin\AyarYonetimRepository;
use Kamelya\Repositories\BildirimRepository;
use Kamelya\Repositories\RandevuRepository;
use Kamelya\Repositories\TalepRepository;
use Kamelya\Services\BildirimService;
use Kamelya\Services\RandevuService;
use Kamelya\Validators\RandevuValidator;

/** POST /api/v1/appointments — F1.2 §3.5 (201 + Location). */
final class RandevuController
{
    private RandevuService $service;

    public function __construct()
    {
        $pdo = Database::baglanti();
        $this->service = new RandevuService(
            $pdo,
            new RandevuRepository($pdo),
            new TalepRepository($pdo),
            null,
            new BildirimService($pdo, new BildirimRepository($pdo), new AyarYonetimRepository($pdo))
        );
    }

    /** @param array<string, string> $rota */
    public function olustur(Request $istek, array $rota = []): void
    {
        try {
            $veri = $istek->govde ?? [];
            $dogrulama = RandevuValidator::dogrula($veri);
            if (!$dogrulama['gecerli']) {
                throw new Hata('VALIDATION_ERROR', 'Doğrulama hatası.', $dogrulama['hatalar'], 422);
            }

            $sonuc = $this->service->olustur($veri);

            header('Location: /api/v1/appointments/' . $sonuc['id']);
            Response::basari($sonuc, null, 201);
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }
}
