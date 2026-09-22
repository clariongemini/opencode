<?php

declare(strict_types=1);

namespace Kamelya\Controllers\Api\V1\Admin;

use Kamelya\Core\Database;
use Kamelya\Core\Hata;
use Kamelya\Core\Request;
use Kamelya\Core\Response;
use Kamelya\Repositories\SehirRepository;
use Kamelya\Services\AuditLogService;
use Kamelya\Services\SehirService;

/** Admin şehir içerik yönetimi — rol: yonetici|editor. */
final class AdminSehirController
{
    private SehirService $service;

    public function __construct()
    {
        $pdo = Database::baglanti();
        $this->service = new SehirService($pdo, new SehirRepository($pdo), new AuditLogService($pdo));
    }

    /** @param array<string, string> $rota */
    public function liste(Request $istek, array $rota = []): void
    {
        Response::basari($this->service->adminListe());
    }

    /** @param array<string, string> $rota */
    public function icerikKaydet(Request $istek, array $rota = []): void
    {
        try {
            if ($istek->kullanici === null) {
                throw new Hata('UNAUTHORIZED', 'Kimlik doğrulama gerekli.', [], 401);
            }

            Response::basari($this->service->icerikKaydet(
                $istek->kullanici, (int) ($rota['id'] ?? 0), $istek->govde ?? [],
                $istek->istemciIp, $istek->baslik('User-Agent') ?? ''
            ));
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }

    /** @param array<string, string> $rota */
    public function bolgeEkle(Request $istek, array $rota = []): void
    {
        try {
            if ($istek->kullanici === null) {
                throw new Hata('UNAUTHORIZED', 'Kimlik doğrulama gerekli.', [], 401);
            }

            $veri = $istek->govde ?? [];
            Response::basari($this->service->bolgeEkle(
                $istek->kullanici, (int) ($rota['id'] ?? 0), (string) ($veri['ilce'] ?? ''),
                $istek->istemciIp, $istek->baslik('User-Agent') ?? ''
            ), null, 201);
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }

    /** @param array<string, string> $rota */
    public function bolgeSil(Request $istek, array $rota = []): void
    {
        try {
            if ($istek->kullanici === null) {
                throw new Hata('UNAUTHORIZED', 'Kimlik doğrulama gerekli.', [], 401);
            }

            $this->service->bolgeSil($istek->kullanici, (int) ($rota['bolgeId'] ?? 0), $istek->istemciIp, $istek->baslik('User-Agent') ?? '');
            Response::basari(['silindi' => true]);
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }
}
