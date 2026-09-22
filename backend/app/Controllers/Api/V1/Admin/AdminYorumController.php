<?php

declare(strict_types=1);

namespace Kamelya\Controllers\Api\V1\Admin;

use Kamelya\Core\Database;
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

/** Yorum moderasyonu — rol: yonetici|editor. Tüm yazma audit'li. */
final class AdminYorumController
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
    public function kuyruk(Request $istek, array $rota = []): void
    {
        $durum = isset($istek->sorgu['durum']) && is_string($istek->sorgu['durum']) ? $istek->sorgu['durum'] : null;
        if ($durum !== null && !in_array($durum, ['bekliyor', 'onaylandi', 'reddedildi'], true)) {
            Response::hata('VALIDATION_ERROR', 'Geçersiz durum filtresi.', [['field' => 'durum', 'issue' => 'invalid']], 422);

            return;
        }

        $sayfa = max(1, (int) ($istek->sorgu['sayfa'] ?? 1));
        $adet = min(100, max(1, (int) ($istek->sorgu['limit'] ?? 20)));

        $sonuc = $this->service->kuyruk($durum, $sayfa, $adet);
        Response::basari($sonuc['satirlar'], ['page' => $sayfa, 'per_page' => $adet, 'total' => $sonuc['toplam']]);
    }

    /** @param array<string, string> $rota */
    public function detay(Request $istek, array $rota = []): void
    {
        try {
            Response::basari($this->service->detay((int) ($rota['id'] ?? 0)));
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }

    /** @param array<string, string> $rota */
    public function onayla(Request $istek, array $rota = []): void
    {
        try {
            Response::basari($this->service->onayla($this->kullanici($istek), (int) ($rota['id'] ?? 0), $istek->istemciIp, $istek->baslik('User-Agent') ?? ''));
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }

    /** @param array<string, string> $rota */
    public function reddet(Request $istek, array $rota = []): void
    {
        try {
            $veri = $istek->govde ?? [];
            Response::basari($this->service->reddet(
                $this->kullanici($istek),
                (int) ($rota['id'] ?? 0),
                (string) ($veri['red_sebebi'] ?? ''),
                $istek->istemciIp,
                $istek->baslik('User-Agent') ?? ''
            ));
        } catch (Hata $hata) {
            Response::hata($hata->hataKodu, $hata->getMessage(), $hata->ayrintilar, $hata->httpDurum);
        }
    }

    /** @param array<string, string> $rota */
    public function oneCikan(Request $istek, array $rota = []): void
    {
        try {
            Response::basari($this->service->oneCikanDegistir($this->kullanici($istek), (int) ($rota['id'] ?? 0), $istek->istemciIp, $istek->baslik('User-Agent') ?? ''));
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
