<?php

declare(strict_types=1);

namespace Kamelya\Controllers\Api\V1\Admin;

use Kamelya\Core\Database;
use Kamelya\Core\Hata;
use Kamelya\Core\Request;
use Kamelya\Core\Response;
use Kamelya\Repositories\KullaniciRepository;
use Kamelya\Services\AuditLogService;
use Kamelya\Services\EkipService;

/** Admin ekip yönetimi — rol: yonetici|editor. */
final class AdminEkipController
{
    private EkipService $service;

    private KullaniciRepository $kullanicilar;

    public function __construct()
    {
        $pdo = Database::baglanti();
        $this->kullanicilar = new KullaniciRepository($pdo);
        $this->service = new EkipService($pdo, $this->kullanicilar, new AuditLogService($pdo));
    }

    /** @param array<string, string> $rota */
    public function liste(Request $istek, array $rota = []): void
    {
        Response::basari($this->kullanicilar->ekipUyeleriGetir());
    }

    /** @param array<string, string> $rota */
    public function guncelle(Request $istek, array $rota = []): void
    {
        try {
            if ($istek->kullanici === null) {
                throw new Hata('UNAUTHORIZED', 'Kimlik doğrulama gerekli.', [], 401);
            }

            Response::basari($this->service->guncelle($istek->kullanici, (int) ($rota['id'] ?? 0), $istek->govde ?? [], $istek->istemciIp, $istek->baslik('User-Agent') ?? ''));
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }
}
