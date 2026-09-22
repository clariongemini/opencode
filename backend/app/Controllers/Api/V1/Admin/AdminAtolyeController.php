<?php

declare(strict_types=1);

namespace Kamelya\Controllers\Api\V1\Admin;

use Kamelya\Core\Database;
use Kamelya\Core\Hata;
use Kamelya\Core\Request;
use Kamelya\Core\Response;
use Kamelya\Repositories\AtolyeRepository;
use Kamelya\Repositories\Admin\BlogYonetimRepository;
use Kamelya\Repositories\Admin\GaleriYonetimRepository;
use Kamelya\Repositories\Admin\ResimYonetimRepository;
use Kamelya\Repositories\Admin\UrunYonetimRepository;
use Kamelya\Services\Admin\YuklemeService;
use Kamelya\Services\AtolyeService;
use Kamelya\Services\AuditLogService;

/** Admin atölye: liste + yükleme + silme — rol: yonetici|editor. */
final class AdminAtolyeController
{
    private AtolyeService $service;

    public function __construct()
    {
        $pdo = Database::baglanti();
        $this->service = new AtolyeService(
            $pdo,
            new AtolyeRepository($pdo),
            new AuditLogService($pdo),
            new YuklemeService(
                $pdo,
                new ResimYonetimRepository($pdo),
                new UrunYonetimRepository($pdo),
                new BlogYonetimRepository($pdo),
                new GaleriYonetimRepository($pdo),
                new AuditLogService($pdo)
            )
        );
    }

    /** @param array<string, string> $rota */
    public function liste(Request $istek, array $rota = []): void
    {
        Response::basari($this->service->adminListe());
    }

    /** @param array<string, string> $rota */
    public function yukle(Request $istek, array $rota = []): void
    {
        try {
            Response::basari($this->service->yukle(
                $this->kullanici($istek),
                $_FILES['file'] ?? [],
                (string) ($_POST['baslik'] ?? ''),
                isset($_POST['aciklama']) ? (string) $_POST['aciklama'] : null,
                $istek->istemciIp,
                $istek->baslik('User-Agent') ?? ''
            ), null, 201);
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
