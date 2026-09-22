<?php

declare(strict_types=1);

namespace Kamelya\Controllers\Api\V1\Admin;

use Kamelya\Core\Database;
use Kamelya\Core\Hata;
use Kamelya\Core\Request;
use Kamelya\Core\Response;
use Kamelya\Repositories\VideoRefRepository;
use Kamelya\Services\AuditLogService;
use Kamelya\Services\VideoRefService;

/** Admin video referans CRUD — rol: yonetici|editor. */
final class AdminVideoRefController
{
    private VideoRefService $service;

    public function __construct()
    {
        $pdo = Database::baglanti();
        $this->service = new VideoRefService($pdo, new VideoRefRepository($pdo), new AuditLogService($pdo));
    }

    /** @param array<string, string> $rota */
    public function liste(Request $istek, array $rota = []): void
    {
        Response::basari($this->service->adminListe());
    }

    /** @param array<string, string> $rota */
    public function olustur(Request $istek, array $rota = []): void
    {
        try {
            Response::basari($this->service->olustur($this->kullanici($istek), $istek->govde ?? [], $istek->istemciIp, $istek->baslik('User-Agent') ?? ''), null, 201);
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }

    /** @param array<string, string> $rota */
    public function guncelle(Request $istek, array $rota = []): void
    {
        try {
            Response::basari($this->service->guncelle($this->kullanici($istek), (int) ($rota['id'] ?? 0), $istek->govde ?? [], $istek->istemciIp, $istek->baslik('User-Agent') ?? ''));
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
