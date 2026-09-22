<?php

declare(strict_types=1);

namespace Kamelya\Controllers\Api\V1\Admin;

use Kamelya\Core\Database;
use Kamelya\Core\Hata;
use Kamelya\Core\Request;
use Kamelya\Core\Response;
use Kamelya\Repositories\Admin\KapasiteYonetimRepository;
use Kamelya\Services\Admin\AdminKapasiteService;
use Kamelya\Services\AuditLogService;
use Kamelya\Validators\Admin\AdminKapasiteValidator;

/** Admin kapasite çarpanı CRUD — silme = pasifleştirme. */
final class AdminKapasiteController
{
    private AdminKapasiteService $service;

    public function __construct()
    {
        $pdo = Database::baglanti();
        $this->service = new AdminKapasiteService($pdo, new KapasiteYonetimRepository($pdo), new AuditLogService($pdo));
    }

    /** @param array<string, string> $rota */
    public function liste(Request $istek, array $rota = []): void
    {
        Response::basari($this->service->liste());
    }

    /** @param array<string, string> $rota */
    public function olustur(Request $istek, array $rota = []): void
    {
        try {
            $veri = $istek->govde ?? [];
            $dogrulama = AdminKapasiteValidator::dogrula($veri);
            if (!$dogrulama['gecerli']) {
                throw new Hata('VALIDATION_ERROR', 'Doğrulama hatası.', $dogrulama['hatalar'], 422);
            }

            Response::basari($this->service->olustur($this->kullanici($istek), $veri, $istek->istemciIp, $istek->baslik('User-Agent') ?? ''), null, 201);
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }

    /** @param array<string, string> $rota */
    public function guncelle(Request $istek, array $rota = []): void
    {
        try {
            $veri = $istek->govde ?? [];
            $dogrulama = AdminKapasiteValidator::dogrula($veri, true);
            if (!$dogrulama['gecerli']) {
                throw new Hata('VALIDATION_ERROR', 'Doğrulama hatası.', $dogrulama['hatalar'], 422);
            }

            Response::basari($this->service->guncelle($this->kullanici($istek), (int) ($rota['id'] ?? 0), $veri, $istek->istemciIp, $istek->baslik('User-Agent') ?? ''));
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }

    /** @param array<string, string> $rota */
    public function sil(Request $istek, array $rota = []): void
    {
        try {
            $this->service->sil($this->kullanici($istek), (int) ($rota['id'] ?? 0), $istek->istemciIp, $istek->baslik('User-Agent') ?? '');
            Response::basari(['silindi' => true]);
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }

    /** @return array<string, mixed> */
    private function kullanici(Request $istek): array
    {
        if ($istek->kullanici === null) {
            throw new Hata('UNAUTHORIZED', 'Kimlik doğrulama gerekli.', [], 401);
        }

        return $istek->kullanici;
    }
}